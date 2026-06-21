<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create supplier offers: warehouses, supplier_products, supplier_product_stock.
 *
 * DDL is rendered from a DBAL Schema via the active platform (portable across
 * MariaDB/MySQL, PostgreSQL and SQLite).
 */
final class Version20260620175352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create warehouses, supplier_products and supplier_product_stock tables';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $target = new Schema();

        $warehouses = $target->createTable('warehouses');
        $warehouses->addColumn('id', 'integer', ['autoincrement' => true]);
        $warehouses->addColumn('supplier_id', 'integer', ['notnull' => false]);
        $warehouses->addColumn('code', 'string', ['length' => 50]);
        $warehouses->addColumn('name', 'string', ['length' => 150]);
        $warehouses->addColumn('country', 'string', ['length' => 2, 'notnull' => false]);
        $warehouses->addColumn('region', 'string', ['length' => 100, 'notnull' => false]);
        $warehouses->addColumn('postcode', 'string', ['length' => 20, 'notnull' => false]);
        $warehouses->addColumn('created_at', 'datetime_immutable');
        $warehouses->setPrimaryKey(['id']);
        $warehouses->addIndex(['supplier_id'], 'IDX_warehouses_supplier');
        $warehouses->addForeignKeyConstraint('suppliers', ['supplier_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_warehouses_supplier');

        $offers = $target->createTable('supplier_products');
        $offers->addColumn('id', 'integer', ['autoincrement' => true]);
        $offers->addColumn('supplier_id', 'integer');
        $offers->addColumn('product_id', 'integer', ['notnull' => false]);
        $offers->addColumn('supplier_ref_code', 'string', ['length' => 100]);
        $offers->addColumn('supplier_sku', 'string', ['length' => 100, 'notnull' => false]);
        $offers->addColumn('match_key', 'string', ['length' => 190, 'notnull' => false]);
        $offers->addColumn('cost_price', 'decimal', ['precision' => 12, 'scale' => 4]);
        $offers->addColumn('currency', 'string', ['length' => 3]);
        $offers->addColumn('stock_quantity', 'integer');
        $offers->addColumn('weight_value', 'decimal', ['precision' => 10, 'scale' => 3, 'notnull' => false]);
        $offers->addColumn('weight_unit', 'string', ['length' => 4, 'notnull' => false]);
        $offers->addColumn('weight_grams', 'decimal', ['precision' => 10, 'scale' => 2, 'notnull' => false]);
        $offers->addColumn('title', 'string', ['length' => 255, 'notnull' => false]);
        $offers->addColumn('short_description', 'text', ['notnull' => false]);
        $offers->addColumn('long_description', 'text', ['notnull' => false]);
        $offers->addColumn('category_raw', 'string', ['length' => 255, 'notnull' => false]);
        $offers->addColumn('image_url', 'string', ['length' => 1000, 'notnull' => false]);
        $offers->addColumn('lead_time_days', 'integer', ['notnull' => false]);
        $offers->addColumn('is_primary', 'boolean');
        $offers->addColumn('last_seen_at', 'datetime_immutable', ['notnull' => false]);
        $offers->addColumn('created_at', 'datetime_immutable');
        $offers->addColumn('updated_at', 'datetime_immutable');
        $offers->setPrimaryKey(['id']);
        $offers->addUniqueIndex(['supplier_id', 'supplier_ref_code'], 'UNIQ_supplier_ref');
        $offers->addIndex(['product_id'], 'IDX_supplier_products_product');
        $offers->addIndex(['match_key'], 'IDX_supplier_products_match');
        $offers->addForeignKeyConstraint('suppliers', ['supplier_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_supplier_products_supplier');
        $offers->addForeignKeyConstraint('products', ['product_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_supplier_products_product');

        $stock = $target->createTable('supplier_product_stock');
        $stock->addColumn('id', 'integer', ['autoincrement' => true]);
        $stock->addColumn('supplier_product_id', 'integer');
        $stock->addColumn('warehouse_id', 'integer');
        $stock->addColumn('quantity', 'integer');
        $stock->addColumn('updated_at', 'datetime_immutable');
        $stock->setPrimaryKey(['id']);
        $stock->addUniqueIndex(['supplier_product_id', 'warehouse_id'], 'UNIQ_offer_warehouse');
        $stock->addForeignKeyConstraint('supplier_products', ['supplier_product_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_sps_offer');
        $stock->addForeignKeyConstraint('warehouses', ['warehouse_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_sps_warehouse');

        foreach ($target->toSql($platform) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE supplier_product_stock');
        $this->addSql('DROP TABLE supplier_products');
        $this->addSql('DROP TABLE warehouses');
    }
}
