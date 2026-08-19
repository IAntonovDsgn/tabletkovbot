<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Migrations;

use App\Infrastructure\Database\Dbal\Repositories\IntakeMarkRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804122747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create intake_marks table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(IntakeMarkRepository::INTAKE_MARKS_TABLE_NAME);

        $table->addColumn(IntakeMarkRepository::ID_COLUMN_NAME, 'bigint', [
            'unsigned' => true,
            'autoincrement' => true,
        ]);

        $table->addColumn(IntakeMarkRepository::CHAT_ID_COLUMN_NAME, 'bigint', [
            'notnull' => true,
        ]);

        $table->addColumn(IntakeMarkRepository::MEDICAMENT_ID_COLUMN_NAME, 'smallint', [
            'unsigned' => true,
            'notnull' => true,
        ]);

        $table->addColumn(IntakeMarkRepository::IS_ACTIVE_COLUMN_NAME, 'boolean', [
            'notnull' => true,
            'default' => 1,
        ]);

        $table->addColumn(IntakeMarkRepository::CREATED_AT_COLUMN_NAME, 'datetime', [
            'notnull' => true,
        ]);

        $table->setPrimaryKey([IntakeMarkRepository::ID_COLUMN_NAME]);
        $table->addOption('engine', 'InnoDB');
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(IntakeMarkRepository::INTAKE_MARKS_TABLE_NAME);
    }
}
