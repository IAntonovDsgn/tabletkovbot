<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Migrations;

use App\Infrastructure\Database\Dbal\Repository\MedicamentRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804131239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create medicaments table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(MedicamentRepository::MEDICAMENT_TABLE_NAME);

        $table->addColumn(MedicamentRepository::ID_COLUMN_NAME, 'bigint', [
            'unsigned' => true,
            'autoincrement' => true,
        ]);

        $table->addColumn(MedicamentRepository::NAME_COLUMN_NAME, 'string', [
            'length' => 255,
            'notnull' => true,
        ]);

        $table->addColumn(MedicamentRepository::CHAT_ID_COLUMN_NAME, 'bigint', [
            'notnull' => true,
        ]);

        $table->addColumn(MedicamentRepository::NOTIFICATION_TIME_COLUMN_NAME, 'datetime', [
            'notnull' => false,
        ]);

        $table->addColumn(MedicamentRepository::IS_ACTIVE_COLUMN_NAME, 'boolean', [
            'notnull' => true,
            'default' => 1,
        ]);

        $table->setPrimaryKey([MedicamentRepository::ID_COLUMN_NAME]);
        $table->addOption('engine', 'InnoDB');
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(MedicamentRepository::MEDICAMENT_TABLE_NAME);
    }
}
