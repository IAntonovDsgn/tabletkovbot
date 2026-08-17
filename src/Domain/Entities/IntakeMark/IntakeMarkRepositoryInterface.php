<?php

declare(strict_types=1);

namespace App\Domain\Entities\IntakeMark;

interface IntakeMarkRepositoryInterface
{
    public function save(IntakeMark $intakeMark): void;

    public function findById(int $id): ?IntakeMark;

    /**
     * @return IntakeMark[]
     */
    public function findByChatId(int $chatId): array;
}
