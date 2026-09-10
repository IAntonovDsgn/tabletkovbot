<?php

declare(strict_types=1);

namespace App\Infrastructure\Dbal\Migrations;

use App\Infrastructure\Dbal\Repositories\MedicamentRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260908120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add medicaments.last_notification_date to dedupe daily reminders';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(MedicamentRepository::MEDICAMENT_TABLE_NAME);

        $table->addColumn(MedicamentRepository::LAST_NOTIFICATION_DATE_COLUMN_NAME, 'date', [
            'notnull' => false,
        ]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(MedicamentRepository::MEDICAMENT_TABLE_NAME);

        $table->dropColumn(MedicamentRepository::LAST_NOTIFICATION_DATE_COLUMN_NAME);
    }
}
