<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Migrations;

use App\Infrastructure\Database\Dbal\Repositories\MedicamentRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change medicaments.notification_time from datetime to time';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(MedicamentRepository::MEDICAMENT_TABLE_NAME);

        $table->changeColumn(MedicamentRepository::NOTIFICATION_TIME_COLUMN_NAME, [
            'type' => Type::getType('time'),
            'notnull' => false,
        ]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(MedicamentRepository::MEDICAMENT_TABLE_NAME);

        $table->changeColumn(MedicamentRepository::NOTIFICATION_TIME_COLUMN_NAME, [
            'type' => Type::getType('datetime'),
            'notnull' => false,
        ]);
    }
}
