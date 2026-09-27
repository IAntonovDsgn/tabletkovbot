<?php

declare(strict_types=1);

namespace App\Infrastructure\Dbal\Migrations;

use App\Infrastructure\Dbal\Repositories\ReportOutboxRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create report_outbox table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(ReportOutboxRepository::REPORT_OUTBOX_TABLE_NAME);

        $table->addColumn(ReportOutboxRepository::ID_COLUMN_NAME, 'bigint', [
            'autoincrement' => true,
            'unsigned' => true,
        ]);

        $table->addColumn(ReportOutboxRepository::CHAT_ID_COLUMN_NAME, 'bigint', [
            'unsigned' => true,
            'notnull' => true,
        ]);

        $table->addColumn(ReportOutboxRepository::START_DATE_COLUMN_NAME, 'date', [
            'unsigned' => true,
            'notnull' => true,
        ]);

        $table->addColumn(ReportOutboxRepository::ATTEMPTS_COLUMN_NAME, 'integer', [
            'unsigned' => true,
            'notnull' => true,
            'default' => 0,
        ]);

        $table->setPrimaryKey([ReportOutboxRepository::ID_COLUMN_NAME]);
        $table->addOption('engine', 'InnoDB');
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(ReportOutboxRepository::REPORT_OUTBOX_TABLE_NAME);
        $schema->dropTable($table->getName());
    }
}
