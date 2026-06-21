<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema (users + refresh_tokens).
 *
 * The DDL is rendered from a DBAL Schema using the active database platform,
 * so this migration works on MariaDB/MySQL, PostgreSQL and SQLite alike
 * (rather than being hard-coded to one vendor's syntax).
 */
final class Version20260618170857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and refresh_tokens tables';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $target = new Schema();

        $users = $target->createTable('users');
        $users->addColumn('id', 'integer', ['autoincrement' => true]);
        $users->addColumn('name', 'string', ['length' => 255]);
        $users->addColumn('email', 'string', ['length' => 180]);
        $users->addColumn('roles', 'json');
        $users->addColumn('password', 'string', ['length' => 255]);
        $users->addColumn('phone', 'string', ['length' => 30, 'notnull' => false]);
        $users->addColumn('job_title', 'string', ['length' => 150, 'notnull' => false]);
        $users->addColumn('active', 'boolean');
        $users->addColumn('created_at', 'datetime_immutable');
        $users->setPrimaryKey(['id']);
        $users->addUniqueIndex(['email'], 'UNIQ_1483A5E9E7927C74');

        $refreshTokens = $target->createTable('refresh_tokens');
        $refreshTokens->addColumn('id', 'integer', ['autoincrement' => true]);
        $refreshTokens->addColumn('refresh_token', 'string', ['length' => 128]);
        $refreshTokens->addColumn('username', 'string', ['length' => 255]);
        $refreshTokens->addColumn('valid', 'datetime');
        $refreshTokens->setPrimaryKey(['id']);
        $refreshTokens->addUniqueIndex(['refresh_token'], 'UNIQ_9BACE7E1C74F2195');

        foreach ($target->toSql($platform) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE users');
    }
}
