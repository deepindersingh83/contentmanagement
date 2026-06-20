<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the master catalogue tables: categories, brands, products, product_images.
 *
 * DDL is rendered from a DBAL Schema via the active platform, so it runs on
 * MariaDB/MySQL, PostgreSQL and SQLite alike.
 */
final class Version20260620174603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create categories, brands, products and product_images tables';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $target = new Schema();

        $categories = $target->createTable('categories');
        $categories->addColumn('id', 'integer', ['autoincrement' => true]);
        $categories->addColumn('name', 'string', ['length' => 150]);
        $categories->addColumn('slug', 'string', ['length' => 180]);
        $categories->addColumn('parent_id', 'integer', ['notnull' => false]);
        $categories->addColumn('created_at', 'datetime_immutable');
        $categories->setPrimaryKey(['id']);
        $categories->addUniqueIndex(['slug'], 'UNIQ_categories_slug');
        $categories->addIndex(['parent_id'], 'IDX_categories_parent');
        $categories->addForeignKeyConstraint('categories', ['parent_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_categories_parent');

        $brands = $target->createTable('brands');
        $brands->addColumn('id', 'integer', ['autoincrement' => true]);
        $brands->addColumn('name', 'string', ['length' => 150]);
        $brands->addColumn('slug', 'string', ['length' => 180]);
        $brands->addColumn('created_at', 'datetime_immutable');
        $brands->setPrimaryKey(['id']);
        $brands->addUniqueIndex(['slug'], 'UNIQ_brands_slug');

        $products = $target->createTable('products');
        $products->addColumn('id', 'integer', ['autoincrement' => true]);
        $products->addColumn('sku', 'string', ['length' => 100]);
        $products->addColumn('gtin', 'string', ['length' => 14, 'notnull' => false]);
        $products->addColumn('title', 'string', ['length' => 255]);
        $products->addColumn('short_description', 'text', ['notnull' => false]);
        $products->addColumn('long_description', 'text', ['notnull' => false]);
        $products->addColumn('brand_id', 'integer', ['notnull' => false]);
        $products->addColumn('category_id', 'integer', ['notnull' => false]);
        $products->addColumn('weight_grams', 'decimal', ['precision' => 10, 'scale' => 2, 'notnull' => false]);
        $products->addColumn('status', 'string', ['length' => 20]);
        $products->addColumn('primary_image_url', 'string', ['length' => 1000, 'notnull' => false]);
        $products->addColumn('sell_price', 'decimal', ['precision' => 12, 'scale' => 2, 'notnull' => false]);
        $products->addColumn('created_at', 'datetime_immutable');
        $products->addColumn('updated_at', 'datetime_immutable');
        $products->setPrimaryKey(['id']);
        $products->addUniqueIndex(['sku'], 'UNIQ_products_sku');
        $products->addIndex(['gtin'], 'IDX_products_gtin');
        $products->addIndex(['brand_id'], 'IDX_products_brand');
        $products->addIndex(['category_id'], 'IDX_products_category');
        $products->addForeignKeyConstraint('brands', ['brand_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_products_brand');
        $products->addForeignKeyConstraint('categories', ['category_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_products_category');

        $images = $target->createTable('product_images');
        $images->addColumn('id', 'integer', ['autoincrement' => true]);
        $images->addColumn('product_id', 'integer');
        $images->addColumn('url', 'string', ['length' => 1000]);
        $images->addColumn('alt', 'string', ['length' => 255, 'notnull' => false]);
        $images->addColumn('position', 'integer');
        $images->setPrimaryKey(['id']);
        $images->addIndex(['product_id'], 'IDX_product_images_product');
        $images->addForeignKeyConstraint('products', ['product_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_product_images_product');

        foreach ($target->toSql($platform) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_images');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE brands');
        $this->addSql('DROP TABLE categories');
    }
}
