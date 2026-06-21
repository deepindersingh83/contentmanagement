<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ImportRunRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A record of one executed import for auditing/provenance.
 */
#[ORM\Entity(repositoryClass: ImportRunRepository::class)]
#[ORM\Table(name: 'import_runs')]
class ImportRun
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImportTemplate::class)]
    #[ORM\JoinColumn(name: 'import_template_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ImportTemplate $template = null;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Supplier $supplier = null;

    /** success | partial | failed */
    #[ORM\Column(length: 20)]
    private string $status = 'success';

    #[ORM\Column]
    private int $rowsTotal = 0;

    #[ORM\Column]
    private int $rowsCreated = 0;

    #[ORM\Column]
    private int $rowsUpdated = 0;

    #[ORM\Column]
    private int $rowsMatched = 0;

    #[ORM\Column]
    private int $rowsFailed = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplate(): ?ImportTemplate
    {
        return $this->template;
    }

    public function setTemplate(?ImportTemplate $template): static
    {
        $this->template = $template;

        return $this;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRowsTotal(): int
    {
        return $this->rowsTotal;
    }

    public function setRowsTotal(int $rowsTotal): static
    {
        $this->rowsTotal = $rowsTotal;

        return $this;
    }

    public function getRowsCreated(): int
    {
        return $this->rowsCreated;
    }

    public function setRowsCreated(int $rowsCreated): static
    {
        $this->rowsCreated = $rowsCreated;

        return $this;
    }

    public function getRowsUpdated(): int
    {
        return $this->rowsUpdated;
    }

    public function setRowsUpdated(int $rowsUpdated): static
    {
        $this->rowsUpdated = $rowsUpdated;

        return $this;
    }

    public function getRowsMatched(): int
    {
        return $this->rowsMatched;
    }

    public function setRowsMatched(int $rowsMatched): static
    {
        $this->rowsMatched = $rowsMatched;

        return $this;
    }

    public function getRowsFailed(): int
    {
        return $this->rowsFailed;
    }

    public function setRowsFailed(int $rowsFailed): static
    {
        $this->rowsFailed = $rowsFailed;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }
}
