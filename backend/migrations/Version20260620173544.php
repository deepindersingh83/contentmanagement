<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the suppliers table and link import_templates.supplier_id to it.
 *
 * The CREATE TABLE DDL is rendered via the active platform (portable across
 * MariaDB/MySQL, PostgreSQL and SQLite); the FK column is added with a portable
 * ADD COLUMN statement.
 */
final class Version20260620173544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create suppliers table and link import_templates to it';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $target = new Schema();

        $t = $target->createTable('suppliers');
        $t->addColumn('id', 'integer', ['autoincrement' => true]);
        $t->addColumn('name', 'string', ['length' => 150]);
        $t->addColumn('code', 'string', ['length' => 50]);
        $t->addColumn('active', 'boolean');
        $t->addColumn('default_currency', 'string', ['length' => 3]);
        $t->addColumn('default_weight_unit', 'string', ['length' => 4]);
        $t->addColumn('website', 'string', ['length' => 255, 'notnull' => false]);
        $t->addColumn('contact_email', 'string', ['length' => 180, 'notnull' => false]);
        $t->addColumn('notes', 'text', ['notnull' => false]);
        $t->addColumn('created_at', 'datetime_immutable');
        $t->setPrimaryKey(['id']);
        $t->addUniqueIndex(['code'], 'UNIQ_suppliers_code');

        foreach ($target->toSql($platform) as $sql) {
            $this->addSql($sql);
        }

        $this->addSql('ALTER TABLE import_templates ADD COLUMN supplier_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_import_templates_supplier ON import_templates (supplier_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_import_templates_supplier');
        $this->addSql('ALTER TABLE import_templates DROP COLUMN supplier_id');
        $this->addSql('DROP TABLE suppliers');
    }
}
