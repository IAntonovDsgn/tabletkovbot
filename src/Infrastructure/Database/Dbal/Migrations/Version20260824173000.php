<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Migrations;

use App\Infrastructure\Database\Dbal\Repositories\OutboxRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add attempts counter to outbox table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(OutboxRepository::MESSAGE_OUTBOX_TABLE_NAME);

        $table->addColumn(OutboxRepository::ATTEMPTS_COLUMN_NAME, 'integer', [
            'unsigned' => true,
            'notnull' => true,
            'default' => 0,
        ]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(OutboxRepository::MESSAGE_OUTBOX_TABLE_NAME);

        $table->dropColumn(OutboxRepository::ATTEMPTS_COLUMN_NAME);
    }
}
