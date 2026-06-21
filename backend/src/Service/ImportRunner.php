<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ImportRun;
use App\Entity\ImportTemplate;
use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Repository\ProductRepository;
use App\Repository\SupplierProductRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Executes an import: fetches the feed (direct/URL/FTP/sFTP, CSV/XLSX/XLS/XML),
 * applies the field mapping built on the Import settings screen, normalises
 * weights, upserts a supplier offer per row, and matches offers to master
 * products by barcode (GTIN).
 */
final class ImportRunner
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SupplierProductRepository $offers,
        private readonly ProductRepository $products,
        private readonly FeedFetcher $fetcher,
        private readonly FeedReader $reader,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(ImportTemplate $template, bool $dryRun, int $previewLimit = 15): array
    {
        try {
            $feed = $this->fetcher->fetch($template);
        } catch (\RuntimeException $e) {
            return ['error' => $e->getMessage()];
        }

        try {
            $parsed = $this->reader->read(
                $feed['path'],
                $template->getFileFormat(),
                $template->getDelimiter(),
                $template->isFirstRowHeaders(),
            );
        } finally {
            if ($feed['cleanup']) {
                @unlink($feed['path']);
            }
        }

        if ($parsed['headers'] === []) {
            return ['error' => 'No columns could be read from the feed. Check the format and delimiter.'];
        }

        $mapping = $template->getMapping() ?? [];
        $supplier = $template->getSupplierRef();

        $total = 0;
        $created = 0;
        $updated = 0;
        $matched = 0;
        $failed = 0;
        $productsCreated = 0;
        $preview = [];

        /** @var array<int, Product> $affected products touched this run (by id) */
        $affected = [];
        /** @var array<string, Product> $matchCache barcode/sku -> product within this run */
        $matchCache = [];

        foreach ($parsed['rows'] as $row) {
            // Skip blank rows.
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            ++$total;

            $get = fn (string $key): ?string => $this->cell($row, $mapping[$key] ?? null);

            $ref = $get('sku') ?? $this->cellByKeyField($row, $mapping, $template);
            if ($ref === null || $ref === '') {
                ++$failed;
                continue;
            }

            $barcode = $get('barcode');
            $weightUnit = $this->fixed($mapping['weight_unit'] ?? null) ?? $supplier?->getDefaultWeightUnit();
            $weightGrams = WeightConverter::toGrams($get('weight'), $weightUnit);

            $data = [
                'supplierRefCode' => $ref,
                'supplierSku' => $get('sku'),
                'matchKey' => $barcode ?: $ref,
                'costPrice' => $this->numeric($get('cost_per_item')),
                'stockQuantity' => (int) ($this->numeric($get('quantity')) ?? '0'),
                'title' => $get('product_title'),
                'longDescription' => $get('product_description'),
                'categoryRaw' => $get('product_type'),
                'imageUrl' => $get('image_url_1'),
                'weightValue' => $get('weight'),
                'weightGrams' => $weightGrams,
            ];

            if ($dryRun) {
                if (count($preview) < $previewLimit) {
                    $willMatch = $barcode ? ($this->products->findOneBy(['gtin' => $barcode]) !== null) : false;
                    $preview[] = $data + ['willMatchProduct' => $willMatch];
                }
                continue;
            }

            if ($supplier === null) {
                ++$failed;
                continue;
            }

            $offer = $this->offers->findOneBySupplierRef($supplier->getId(), $ref);
            $isNew = $offer === null;
            if ($offer === null) {
                $offer = new SupplierProduct();
                $offer->setSupplier($supplier);
                $offer->setSupplierRefCode($ref);
                $offer->setCurrency($supplier->getDefaultCurrency());
            }

            $offer->setSupplierSku($data['supplierSku']);
            $offer->setMatchKey($data['matchKey']);
            $offer->setCostPrice($data['costPrice'] ?? '0');
            $offer->setStockQuantity($data['stockQuantity']);
            $offer->setTitle($data['title']);
            $offer->setLongDescription($data['longDescription']);
            $offer->setCategoryRaw($data['categoryRaw']);
            $offer->setImageUrl($data['imageUrl']);
            $offer->setWeightValue($data['weightValue']);
            $offer->setWeightUnit($weightUnit);
            $offer->setWeightGrams($weightGrams);
            $offer->setLastSeenAt(new \DateTimeImmutable());

            // Match to an existing master product by barcode/GTIN.
            if ($offer->getProduct() === null && $barcode) {
                $product = $matchCache[$barcode]
                    ?? $this->products->findOneBy(['gtin' => $barcode]);
                if ($product instanceof Product) {
                    $offer->setProduct($product);
                    ++$matched;
                }
            }

            // Otherwise optionally create a master product from this offer.
            if ($offer->getProduct() === null && $template->isAutoCreateProducts() && ($data['title'] !== null || $ref !== '')) {
                $sku = $barcode ?: ($supplier->getCode().'-'.$ref);
                $product = $matchCache[$sku] ?? $this->products->findOneBySku($sku);
                if (!$product instanceof Product) {
                    $product = new Product();
                    $product->setSku($sku);
                    $product->setGtin($barcode);
                    $product->setTitle($data['title'] ?? $ref);
                    $product->setStatus('draft');
                    $this->em->persist($product);
                    ++$productsCreated;
                    if ($barcode) {
                        $matchCache[$barcode] = $product;
                    }
                    $matchCache[$sku] = $product;
                }
                $offer->setProduct($product);
            }

            $this->em->persist($offer);
            $isNew ? ++$created : ++$updated;

            if ($offer->getProduct() !== null) {
                $affected[spl_object_id($offer->getProduct())] = $offer->getProduct();
            }

            if (($created + $updated) % 200 === 0) {
                $this->em->flush();
            }
        }

        if ($dryRun) {
            return ['dryRun' => true, 'total' => $total, 'failed' => $failed, 'preview' => $preview];
        }

        $this->em->flush();

        // Re-pick the primary offer per affected product and roll its data up
        // into any empty product fields (non-destructive).
        $primariesSet = 0;
        foreach ($affected as $product) {
            if ($this->repickPrimary($product)) {
                ++$primariesSet;
            }
        }
        $this->em->flush();

        $run = new ImportRun();
        $run->setTemplate($template);
        $run->setSupplier($supplier);
        $run->setRowsTotal($total);
        $run->setRowsCreated($created);
        $run->setRowsUpdated($updated);
        $run->setRowsMatched($matched);
        $run->setRowsFailed($failed);
        $run->setStatus($failed === 0 ? 'success' : ($created + $updated > 0 ? 'partial' : 'failed'));
        $run->setMessage(sprintf('%d products auto-created; %d primary offers set.', $productsCreated, $primariesSet));
        $run->setFinishedAt(new \DateTimeImmutable());
        $this->em->persist($run);
        $this->em->flush();

        return [
            'dryRun' => false,
            'runId' => $run->getId(),
            'status' => $run->getStatus(),
            'total' => $total,
            'created' => $created,
            'updated' => $updated,
            'matched' => $matched,
            'failed' => $failed,
            'productsCreated' => $productsCreated,
            'primariesSet' => $primariesSet,
        ];
    }

    /**
     * Choose the primary offer for a product (in-stock + cheapest, falling back
     * to cheapest overall) and fill empty product fields from it.
     */
    private function repickPrimary(Product $product): bool
    {
        /** @var \App\Entity\SupplierProduct[] $offers */
        $offers = $this->offers->findBy(['product' => $product]);
        if ($offers === []) {
            return false;
        }

        $inStock = array_filter($offers, fn ($o) => $o->getStockQuantity() > 0);
        $pool = $inStock !== [] ? $inStock : $offers;
        usort($pool, fn ($a, $b) => (float) $a->getCostPrice() <=> (float) $b->getCostPrice());
        $primary = $pool[0];

        foreach ($offers as $offer) {
            $offer->setIsPrimary($offer === $primary);
        }

        // Non-destructive roll-up: only fill fields that are currently empty.
        if (($product->getTitle() === '' || $product->getTitle() === null) && $primary->getTitle()) {
            $product->setTitle($primary->getTitle());
        }
        if ($product->getShortDescription() === null && $primary->getShortDescription() !== null) {
            $product->setShortDescription($primary->getShortDescription());
        }
        if ($product->getLongDescription() === null && $primary->getLongDescription() !== null) {
            $product->setLongDescription($primary->getLongDescription());
        }
        if ($product->getPrimaryImageUrl() === null && $primary->getImageUrl() !== null) {
            $product->setPrimaryImageUrl($primary->getImageUrl());
        }
        if ($product->getWeightGrams() === null && $primary->getWeightGrams() !== null) {
            $product->setWeightGrams($primary->getWeightGrams());
        }

        return true;
    }

    /**
     * @param array<string, string> $row
     */
    private function cell(array $row, mixed $column): ?string
    {
        if (!is_string($column) || $column === '' || !array_key_exists($column, $row)) {
            return null;
        }
        $value = trim((string) $row[$column]);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, mixed> $mapping
     */
    private function cellByKeyField(array $row, array $mapping, ImportTemplate $template): ?string
    {
        $map = [
            'sku' => 'sku',
            'barcode' => 'barcode',
            'title' => 'product_title',
            'model' => 'sku',
            'id' => 'sku',
        ];
        $field = $map[$template->getKeyField()] ?? 'sku';

        return $this->cell($row, $mapping[$field] ?? null);
    }

    private function fixed(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function numeric(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $clean = str_replace([',', '$', ' '], ['.', '', ''], $value);

        return is_numeric($clean) ? $clean : null;
    }
}
