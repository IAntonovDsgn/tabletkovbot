<?php

declare(strict_types=1);

namespace App\Domain\Entities\IntakeMark;

interface IntakeMarkRepositoryInterface
{
    public function insert(IntakeMark $intakeMark): int;

    public function update(IntakeMark $intakeMark): void;

    public function findById(int $id): ?IntakeMark;

    /**
     * @return IntakeMark[]
     */
    public function findByChatId(int $chatId): array;
}
