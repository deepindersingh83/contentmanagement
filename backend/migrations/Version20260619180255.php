<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the import_templates table.
 *
 * DDL is rendered from a DBAL Schema via the active platform, so it runs on
 * MariaDB/MySQL, PostgreSQL and SQLite alike.
 */
final class Version20260619180255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create import_templates table';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $target = new Schema();

        $t = $target->createTable('import_templates');
        $t->addColumn('id', 'integer', ['autoincrement' => true]);
        $t->addColumn('name', 'string', ['length' => 255]);
        $t->addColumn('supplier', 'string', ['length' => 150, 'notnull' => false]);
        $t->addColumn('source', 'string', ['length' => 20]);
        $t->addColumn('source_url', 'string', ['length' => 1000, 'notnull' => false]);
        $t->addColumn('file_format', 'string', ['length' => 10]);
        $t->addColumn('key_field', 'string', ['length' => 50]);
        $t->addColumn('first_row_headers', 'boolean');
        $t->addColumn('zip_archive', 'boolean');
        $t->addColumn('import_translations', 'boolean');
        $t->addColumn('original_filename', 'string', ['length' => 255, 'notnull' => false]);
        $t->addColumn('stored_filename', 'string', ['length' => 255, 'notnull' => false]);
        $t->addColumn('owner_id', 'integer', ['notnull' => false]);
        $t->addColumn('created_at', 'datetime_immutable');
        $t->setPrimaryKey(['id']);
        $t->addIndex(['owner_id'], 'IDX_import_templates_owner');
        $t->addForeignKeyConstraint('users', ['owner_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_import_templates_owner');

        foreach ($target->toSql($platform) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE import_templates');
    }
}
