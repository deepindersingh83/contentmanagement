<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add per-source fields to import_templates (CSV delimiter + FTP/sFTP details).
 *
 * Uses portable "ADD COLUMN" statements that run on MariaDB/MySQL and SQLite.
 */
final class Version20260619182858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delimiter and FTP/sFTP fields to import_templates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_templates ADD COLUMN csv_delimiter VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_templates ADD COLUMN ftp_server VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_templates ADD COLUMN ftp_username VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_templates ADD COLUMN ftp_password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_templates ADD COLUMN ftp_port INT DEFAULT NULL');
        $this->addSql('ALTER TABLE import_templates ADD COLUMN ftp_path VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_templates ADD COLUMN ftp_passive_mode BOOLEAN DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE import_templates ADD COLUMN remove_after_import BOOLEAN DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_templates DROP COLUMN csv_delimiter');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN ftp_server');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN ftp_username');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN ftp_password');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN ftp_port');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN ftp_path');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN ftp_passive_mode');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN remove_after_import');
    }
}
