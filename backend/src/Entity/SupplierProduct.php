<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SupplierProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A supplier's offer for a product. One row per (supplier × product).
 * Only cost_price + stock_quantity are required, so "stock + price only"
 * feeds still produce a valid offer.
 */
#[ORM\Entity(repositoryClass: SupplierProductRepository::class)]
#[ORM\Table(name: 'supplier_products')]
#[ORM\UniqueConstraint(name: 'UNIQ_supplier_ref', columns: ['supplier_id', 'supplier_ref_code'])]
#[ORM\HasLifecycleCallbacks]
class SupplierProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Supplier $supplier = null;

    /** Null until the offer is matched to a master product. */
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $supplierRefCode = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $supplierSku = null;

    /** gtin or normalised ref used to dedupe/match. */
    #[ORM\Column(length: 190, nullable: true)]
    private ?string $matchKey = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4)]
    private string $costPrice = '0';

    #[ORM\Column(length: 3)]
    private string $currency = 'AUD';

    #[ORM\Column]
    private int $stockQuantity = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3, nullable: true)]
    private ?string $weightValue = null;

    /** g | kg | lb | oz */
    #[ORM\Column(length: 4, nullable: true)]
    private ?string $weightUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $weightGrams = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $longDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categoryRaw = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $leadTimeDays = null;

    #[ORM\Column]
    private bool $isPrimary = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    /** @var Collection<int, SupplierProductStock> */
    #[ORM\OneToMany(mappedBy: 'supplierProduct', targetEntity: SupplierProductStock::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $stockLevels;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->stockLevels = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getSupplierRefCode(): string
    {
        return $this->supplierRefCode;
    }

    public function setSupplierRefCode(string $supplierRefCode): static
    {
        $this->supplierRefCode = $supplierRefCode;

        return $this;
    }

    public function getSupplierSku(): ?string
    {
        return $this->supplierSku;
    }

    public function setSupplierSku(?string $supplierSku): static
    {
        $this->supplierSku = $supplierSku;

        return $this;
    }

    public function getMatchKey(): ?string
    {
        return $this->matchKey;
    }

    public function setMatchKey(?string $matchKey): static
    {
        $this->matchKey = $matchKey;

        return $this;
    }

    public function getCostPrice(): string
    {
        return $this->costPrice;
    }

    public function setCostPrice(string $costPrice): static
    {
        $this->costPrice = $costPrice;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = strtoupper($currency);

        return $this;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): static
    {
        $this->stockQuantity = $stockQuantity;

        return $this;
    }

    public function getWeightValue(): ?string
    {
        return $this->weightValue;
    }

    public function setWeightValue(?string $weightValue): static
    {
        $this->weightValue = $weightValue;

        return $this;
    }

    public function getWeightUnit(): ?string
    {
        return $this->weightUnit;
    }

    public function setWeightUnit(?string $weightUnit): static
    {
        $this->weightUnit = $weightUnit;

        return $this;
    }

    public function getWeightGrams(): ?string
    {
        return $this->weightGrams;
    }

    public function setWeightGrams(?string $weightGrams): static
    {
        $this->weightGrams = $weightGrams;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    public function setLongDescription(?string $longDescription): static
    {
        $this->longDescription = $longDescription;

        return $this;
    }

    public function getCategoryRaw(): ?string
    {
        return $this->categoryRaw;
    }

    public function setCategoryRaw(?string $categoryRaw): static
    {
        $this->categoryRaw = $categoryRaw;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getLeadTimeDays(): ?int
    {
        return $this->leadTimeDays;
    }

    public function setLeadTimeDays(?int $leadTimeDays): static
    {
        $this->leadTimeDays = $leadTimeDays;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): static
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): static
    {
        $this->lastSeenAt = $lastSeenAt;

        return $this;
    }

    /** @return Collection<int, SupplierProductStock> */
    public function getStockLevels(): Collection
    {
        return $this->stockLevels;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
