<?php

namespace App\Domain\IntakeMark;

use DateTimeImmutable;

class IntakeMarkFactory extends IntakeMark
{
    public static function create(
        int $chatId,
        int $medicamentId,
    ): IntakeMark
    {
        return new IntakeMark
        (
            $chatId,
            $medicamentId,
            true,
        );
    }

    public static function restore(
        int $chatId,
        int $medicamentId,
        bool $isActive,
        DateTimeImmutable $createdAt,
        int $id
    ): IntakeMark
    {
        return new IntakeMark(
            $chatId,
            $medicamentId,
            $isActive,
            $createdAt,
            $id
        );
    }
}
