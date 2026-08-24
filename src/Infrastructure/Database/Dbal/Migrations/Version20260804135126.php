<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Migrations;

use App\Infrastructure\Database\Dbal\Repositories\SessionRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804135126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create session table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(SessionRepository::SESSION_TABLE_NAME);

        $table->addColumn(SessionRepository::ID_COLUMN_NAME, 'bigint', [
            'unsigned' => true,
            'autoincrement' => true,
        ]);

        $table->addColumn(SessionRepository::CHAT_ID_COLUMN_NAME, 'bigint', [
            'notnull' => true,
            'unsigned' => true,
        ]);

        $table->addColumn(SessionRepository::STATE_COLUMN_NAME, 'string', [
            'notnull' => true,
            'length' => 255,
        ]);

        $table->addColumn(SessionRepository::IS_NOTIFICATION_ENABLED_COLUMN_NAME, 'boolean', [
            'notnull' => true,
            'default' => 1,
        ]);

        $table->addColumn(SessionRepository::PAYLOAD_COLUMN_NAME, 'string', [
            'notnull' => false,
            'length' => 255,
        ]);

        $table->setPrimaryKey([SessionRepository::CHAT_ID_COLUMN_NAME]);
        $table->addOption('engine', 'InnoDB');
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(SessionRepository::SESSION_TABLE_NAME);
    }
}
