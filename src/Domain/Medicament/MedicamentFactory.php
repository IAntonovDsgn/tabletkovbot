<?php

namespace App\Domain\Medicament;

use DateTimeImmutable;

class MedicamentFactory extends Medicament
{
    public static function create(
        string $name,
        int $userId,
        ?DateTimeImmutable $notificationTime = null
    ): Medicament
    {
        return new Medicament(
            $name,
            $userId,
            true,
            $notificationTime
        );
    }
}
