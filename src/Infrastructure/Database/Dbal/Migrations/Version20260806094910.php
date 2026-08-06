<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Migrations;

use App\Infrastructure\Database\Dbal\Repository\OutboxRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806094910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create outbox table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(OutboxRepository::MESSAGE_OUTBOX_TABLE_NAME);

        $table->addColumn(OutboxRepository::ID_COLUMN_NAME, 'bigint', [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);

        $table->addColumn(OutboxRepository::CHAT_ID_COLUMN_NAME, 'bigint', [
            'unsigned' => true,
            'notnull' => true,
        ]);

        $table->addColumn(OutboxRepository::STATUS_COLUMN_NAME, 'string', [
            'notnull' => true,
            'length' => 20,
            'default' => OutboxRepository::PENDING_STATUS,
        ]);

        $table->addColumn(OutboxRepository::TEXT_COLUMN_NAME, 'string', [
            'notnull' => false,
            'length' => 255,
        ]);

        $table->addColumn(OutboxRepository::BUTTONS_COLUMN_NAME, 'string', [
            'notnull' => false,
            'length' => 255,
        ]);

        $table->setPrimaryKey([OutboxRepository::ID_COLUMN_NAME]);
        $table->addOption('engine', 'InnoDB');
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(OutboxRepository::MESSAGE_OUTBOX_TABLE_NAME);
    }
}
