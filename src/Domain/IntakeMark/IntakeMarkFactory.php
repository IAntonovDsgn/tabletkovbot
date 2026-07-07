<?php

namespace App\Domain\IntakeMark;

use DateTimeImmutable;

class IntakeMarkFactory extends IntakeMark
{
    public static function create(
        int $userId,
        int $medicamentId,
    ): IntakeMark
    {
        return new IntakeMark
        (
            $userId,
            $medicamentId,
            true,
        );
    }

    public static function restore(
        int $userId,
        int $medicamentId,
        bool $isActive,
        DateTimeImmutable $createdAt,
        int $id
    ): IntakeMark
    {
        return new IntakeMark(
            $userId,
            $medicamentId,
            $isActive,
            $createdAt,
            $id
        );
    }
}
