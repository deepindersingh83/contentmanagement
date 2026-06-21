<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the auto_create_products flag to import_templates.
 */
final class Version20260621071825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add auto_create_products to import_templates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_templates ADD COLUMN auto_create_products BOOLEAN DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE import_templates DROP COLUMN auto_create_products');
    }
}
