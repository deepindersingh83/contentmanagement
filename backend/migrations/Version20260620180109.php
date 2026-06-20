<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the import_runs table (audit of executed imports).
 */
final class Version20260620180109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create import_runs table';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $target = new Schema();

        $t = $target->createTable('import_runs');
        $t->addColumn('id', 'integer', ['autoincrement' => true]);
        $t->addColumn('import_template_id', 'integer', ['notnull' => false]);
        $t->addColumn('supplier_id', 'integer', ['notnull' => false]);
        $t->addColumn('status', 'string', ['length' => 20]);
        $t->addColumn('rows_total', 'integer');
        $t->addColumn('rows_created', 'integer');
        $t->addColumn('rows_updated', 'integer');
        $t->addColumn('rows_matched', 'integer');
        $t->addColumn('rows_failed', 'integer');
        $t->addColumn('message', 'text', ['notnull' => false]);
        $t->addColumn('started_at', 'datetime_immutable');
        $t->addColumn('finished_at', 'datetime_immutable', ['notnull' => false]);
        $t->setPrimaryKey(['id']);
        $t->addIndex(['import_template_id'], 'IDX_import_runs_template');
        $t->addIndex(['supplier_id'], 'IDX_import_runs_supplier');
        $t->addForeignKeyConstraint('import_templates', ['import_template_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_import_runs_template');
        $t->addForeignKeyConstraint('suppliers', ['supplier_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_import_runs_supplier');

        foreach ($target->toSql($platform) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE import_runs');
    }
}
