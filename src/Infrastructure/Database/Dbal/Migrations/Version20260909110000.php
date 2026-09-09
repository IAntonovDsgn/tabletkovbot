<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Migrations;

use App\Infrastructure\Database\Dbal\Repositories\ReportRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type to message_outbox and create report_requests table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(ReportRepository::REPORT_TABLE_NAME);

        $table->addColumn(ReportRepository::ID_COLUMN_NAME, 'bigint', [
            'autoincrement' => true,
            'unsigned' => true,
        ]);

        $table->addColumn(ReportRepository::CHAT_ID_COLUMN_NAME, 'bigint', [
            'unsigned' => true,
            'notnull' => true,
        ]);

        $table->addColumn(ReportRepository::START_DATE_COLUMN_NAME, 'date', [
            'notnull' => true,
        ]);

        $table->addColumn(ReportRepository::CREATED_AT_COLUMN_NAME, 'datetime',[
            'notnull' => true,
        ]);

        $table->setPrimaryKey([ReportRepository::ID_COLUMN_NAME]);
        $table->addOption('engine', 'InnoDB');
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(ReportRepository::REPORT_TABLE_NAME);
        $schema->dropTable($table->getName());
    }
}
