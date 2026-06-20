<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add scheduling columns to import_templates.
 */
final class Version20260620182042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add schedule fields to import_templates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE import_templates ADD COLUMN schedule_frequency VARCHAR(20) DEFAULT 'manual' NOT NULL");
        $this->addSql('ALTER TABLE import_templates ADD COLUMN last_run_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE import_templates ADD COLUMN next_run_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_templates DROP COLUMN schedule_frequency');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN last_run_at');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN next_run_at');
    }
}
