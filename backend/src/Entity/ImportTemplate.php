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
