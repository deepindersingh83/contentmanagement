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
 * Executes an import: reads the uploaded CSV, applies the field mapping built on
 * the Import settings screen, normalises weights, upserts a supplier offer per
 * row, and matches offers to master products by barcode (GTIN).
 */
final class ImportRunner
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SupplierProductRepository $offers,
        private readonly ProductRepository $products,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(ImportTemplate $template, bool $dryRun, int $previewLimit = 15): array
    {
        if ($template->getSource() !== 'direct') {
            return ['error' => 'Only direct uploads can be run currently (URL/FTP fetching is coming next).'];
        }
        if ($template->getFileFormat() !== 'csv') {
            return ['error' => 'Only CSV files can be processed currently.'];
        }
        $stored = $template->getStoredFilename();
        $path = $stored !== null ? $this->projectDir.'/var/uploads/imports/'.$stored : null;
        if ($path === null || !is_file($path)) {
            return ['error' => 'The uploaded file for this template could not be found.'];
        }

        $delimiter = $template->getDelimiter() ?: ',';
        if ($delimiter === '\t') {
            $delimiter = "\t";
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ['error' => 'Could not open the uploaded file.'];
        }

        $mapping = $template->getMapping() ?? [];
        $supplier = $template->getSupplierRef();

        $headers = [];
        $rowIndex = 0;
        $total = 0;
        $created = 0;
        $updated = 0;
        $matched = 0;
        $failed = 0;
        $preview = [];

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if ($rowIndex === 0 && $template->isFirstRowHeaders()) {
                foreach ($row as $i => $h) {
                    $headers[trim((string) $h)] = $i;
                }
                ++$rowIndex;
                continue;
            }
            if ($headers === [] && !$template->isFirstRowHeaders()) {
                foreach ($row as $i => $_) {
                    $headers['Column '.($i + 1)] = $i;
                }
            }
            // Skip fully empty lines.
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                ++$rowIndex;
                continue;
            }

            ++$total;
            $get = fn (string $key): ?string => $this->cell($row, $headers, $mapping[$key] ?? null);

            $ref = $get('sku') ?? $this->cellByKeyField($row, $headers, $mapping, $template);
            if ($ref === null || $ref === '') {
                ++$failed;
                ++$rowIndex;
                continue;
            }

            $barcode = $get('barcode');
            $weightGrams = WeightConverter::toGrams(
                $get('weight'),
                $this->fixed($mapping['weight_unit'] ?? null) ?? $supplier?->getDefaultWeightUnit(),
            );

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
                ++$rowIndex;
                continue;
            }

            if ($supplier === null) {
                ++$failed;
                ++$rowIndex;
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
            $offer->setWeightUnit($this->fixed($mapping['weight_unit'] ?? null) ?? $supplier->getDefaultWeightUnit());
            $offer->setWeightGrams($weightGrams);
            $offer->setLastSeenAt(new \DateTimeImmutable());

            // Match to a master product by barcode/GTIN if not already linked.
            if ($offer->getProduct() === null && $barcode) {
                $product = $this->products->findOneBy(['gtin' => $barcode]);
                if ($product instanceof Product) {
                    $offer->setProduct($product);
                    ++$matched;
                }
            }

            $this->em->persist($offer);
            $isNew ? ++$created : ++$updated;

            // Flush in batches to keep memory bounded on large feeds.
            if (($created + $updated) % 200 === 0) {
                $this->em->flush();
            }

            ++$rowIndex;
        }

        fclose($handle);

        if ($dryRun) {
            return ['dryRun' => true, 'total' => $total, 'failed' => $failed, 'preview' => $preview];
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
        ];
    }

    /**
     * @param array<int, string> $row
     * @param array<string, int> $headers
     */
    private function cell(array $row, array $headers, mixed $column): ?string
    {
        if (!is_string($column) || $column === '' || !array_key_exists($column, $headers)) {
            return null;
        }
        $value = $row[$headers[$column]] ?? null;
        $value = $value === null ? '' : trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Falls back to the template's "key for product identification" column when
     * no explicit SKU mapping is present.
     *
     * @param array<int, string> $row
     * @param array<string, int> $headers
     * @param array<string, mixed> $mapping
     */
    private function cellByKeyField(array $row, array $headers, array $mapping, ImportTemplate $template): ?string
    {
        $map = [
            'sku' => 'sku',
            'barcode' => 'barcode',
            'title' => 'product_title',
            'model' => 'sku',
            'id' => 'sku',
        ];
        $field = $map[$template->getKeyField()] ?? 'sku';

        return $this->cell($row, $headers, $mapping[$field] ?? null);
    }

    /** Fixed-value mapping entries (e.g. weight unit) are stored as plain strings. */
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
