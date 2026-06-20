<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the mapping (JSON) column to import_templates for the field-mapping step.
 */
final class Version20260619183737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mapping column to import_templates';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isSqlite = str_contains(strtolower($platform::class), 'sqlite');
        $type = $isSqlite ? 'CLOB' : 'LONGTEXT';

        $this->addSql("ALTER TABLE import_templates ADD COLUMN mapping $type DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_templates DROP COLUMN mapping');
    }
}
