<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ImportTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ImportTemplateRepository::class)]
#[ORM\Table(name: 'import_templates')]
class ImportTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $supplier = null;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Supplier $supplierRef = null;

    /** direct | url | ftp | sftp */
    #[ORM\Column(length: 20)]
    private string $source = 'direct';

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $sourceUrl = null;

    /** xlsx | xls | csv | xml */
    #[ORM\Column(length: 10)]
    private string $fileFormat = 'xlsx';

    /** title | sku | barcode | model | id */
    #[ORM\Column(length: 50)]
    private string $keyField = 'title';

    #[ORM\Column]
    private bool $firstRowHeaders = true;

    #[ORM\Column]
    private bool $zipArchive = false;

    #[ORM\Column]
    private bool $importTranslations = false;

    /** CSV delimiter (only meaningful when fileFormat = csv). */
    #[ORM\Column(name: 'csv_delimiter', length: 10, nullable: true)]
    private ?string $delimiter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ftpServer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ftpUsername = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ftpPassword = null;

    #[ORM\Column(nullable: true)]
    private ?int $ftpPort = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $ftpPath = null;

    #[ORM\Column]
    private bool $ftpPassiveMode = true;

    #[ORM\Column]
    private bool $removeAfterImport = false;

    /** Create a master product for offers that don't match an existing one. */
    #[ORM\Column]
    private bool $autoCreateProducts = false;

    /** Field -> column / value mapping built on the Import settings (step 2) screen. */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $mapping = null;

    /** manual | hourly | daily | weekly */
    #[ORM\Column(length: 20)]
    private string $scheduleFrequency = 'manual';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastRunAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $nextRunAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalFilename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storedFilename = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $owner = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function setSupplier(?string $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSupplierRef(): ?Supplier
    {
        return $this->supplierRef;
    }

    public function setSupplierRef(?Supplier $supplierRef): static
    {
        $this->supplierRef = $supplierRef;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): static
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    public function getFileFormat(): string
    {
        return $this->fileFormat;
    }

    public function setFileFormat(string $fileFormat): static
    {
        $this->fileFormat = $fileFormat;

        return $this;
    }

    public function getKeyField(): string
    {
        return $this->keyField;
    }

    public function setKeyField(string $keyField): static
    {
        $this->keyField = $keyField;

        return $this;
    }

    public function isFirstRowHeaders(): bool
    {
        return $this->firstRowHeaders;
    }

    public function setFirstRowHeaders(bool $firstRowHeaders): static
    {
        $this->firstRowHeaders = $firstRowHeaders;

        return $this;
    }

    public function isZipArchive(): bool
    {
        return $this->zipArchive;
    }

    public function setZipArchive(bool $zipArchive): static
    {
        $this->zipArchive = $zipArchive;

        return $this;
    }

    public function isImportTranslations(): bool
    {
        return $this->importTranslations;
    }

    public function setImportTranslations(bool $importTranslations): static
    {
        $this->importTranslations = $importTranslations;

        return $this;
    }

    public function getDelimiter(): ?string
    {
        return $this->delimiter;
    }

    public function setDelimiter(?string $delimiter): static
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function getFtpServer(): ?string
    {
        return $this->ftpServer;
    }

    public function setFtpServer(?string $ftpServer): static
    {
        $this->ftpServer = $ftpServer;

        return $this;
    }

    public function getFtpUsername(): ?string
    {
        return $this->ftpUsername;
    }

    public function setFtpUsername(?string $ftpUsername): static
    {
        $this->ftpUsername = $ftpUsername;

        return $this;
    }

    public function getFtpPassword(): ?string
    {
        return $this->ftpPassword;
    }

    public function setFtpPassword(?string $ftpPassword): static
    {
        $this->ftpPassword = $ftpPassword;

        return $this;
    }

    public function getFtpPort(): ?int
    {
        return $this->ftpPort;
    }

    public function setFtpPort(?int $ftpPort): static
    {
        $this->ftpPort = $ftpPort;

        return $this;
    }

    public function getFtpPath(): ?string
    {
        return $this->ftpPath;
    }

    public function setFtpPath(?string $ftpPath): static
    {
        $this->ftpPath = $ftpPath;

        return $this;
    }

    public function isFtpPassiveMode(): bool
    {
        return $this->ftpPassiveMode;
    }

    public function setFtpPassiveMode(bool $ftpPassiveMode): static
    {
        $this->ftpPassiveMode = $ftpPassiveMode;

        return $this;
    }

    public function isRemoveAfterImport(): bool
    {
        return $this->removeAfterImport;
    }

    public function setRemoveAfterImport(bool $removeAfterImport): static
    {
        $this->removeAfterImport = $removeAfterImport;

        return $this;
    }

    public function isAutoCreateProducts(): bool
    {
        return $this->autoCreateProducts;
    }

    public function setAutoCreateProducts(bool $autoCreateProducts): static
    {
        $this->autoCreateProducts = $autoCreateProducts;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getMapping(): ?array
    {
        return $this->mapping;
    }

    /** @param array<string, mixed>|null $mapping */
    public function setMapping(?array $mapping): static
    {
        $this->mapping = $mapping;

        return $this;
    }

    public function getScheduleFrequency(): string
    {
        return $this->scheduleFrequency;
    }

    public function setScheduleFrequency(string $scheduleFrequency): static
    {
        $this->scheduleFrequency = $scheduleFrequency;

        return $this;
    }

    public function getLastRunAt(): ?\DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    public function setLastRunAt(?\DateTimeImmutable $lastRunAt): static
    {
        $this->lastRunAt = $lastRunAt;

        return $this;
    }

    public function getNextRunAt(): ?\DateTimeImmutable
    {
        return $this->nextRunAt;
    }

    public function setNextRunAt(?\DateTimeImmutable $nextRunAt): static
    {
        $this->nextRunAt = $nextRunAt;

        return $this;
    }

    public function intervalForSchedule(): ?\DateInterval
    {
        return match ($this->scheduleFrequency) {
            'hourly' => new \DateInterval('PT1H'),
            'daily' => new \DateInterval('P1D'),
            'weekly' => new \DateInterval('P7D'),
            default => null,
        };
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(?string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getStoredFilename(): ?string
    {
        return $this->storedFilename;
    }

    public function setStoredFilename(?string $storedFilename): static
    {
        $this->storedFilename = $storedFilename;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
