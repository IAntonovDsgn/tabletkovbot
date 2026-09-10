<?php

declare(strict_types=1);

namespace App\Infrastructure\Dbal\Migrations;

use App\Infrastructure\Dbal\Repositories\MessageOutboxRepository;
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
        $table = $schema->createTable(MessageOutboxRepository::MESSAGE_OUTBOX_TABLE_NAME);

        $table->addColumn(MessageOutboxRepository::ID_COLUMN_NAME, 'bigint', [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);

        $table->addColumn(MessageOutboxRepository::CHAT_ID_COLUMN_NAME, 'bigint', [
            'unsigned' => true,
            'notnull' => true,
        ]);

        $table->addColumn(MessageOutboxRepository::TEXT_COLUMN_NAME, 'text', [
            'notnull' => false,
        ]);

        $table->addColumn(MessageOutboxRepository::BUTTONS_COLUMN_NAME, 'text', [
            'notnull' => false,
        ]);

        $table->addColumn(MessageOutboxRepository::TYPE_COLUMN_NAME, 'text', [
            'notnull' => false,
        ]);

        $table->addColumn(MessageOutboxRepository::ATTEMPTS_COLUMN_NAME, 'integer', [
            'unsigned' => true,
            'notnull' => true,
            'default' => 0,
        ]);

        $table->setPrimaryKey([MessageOutboxRepository::ID_COLUMN_NAME]);
        $table->addOption('engine', 'InnoDB');
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(MessageOutboxRepository::MESSAGE_OUTBOX_TABLE_NAME);
    }
}
