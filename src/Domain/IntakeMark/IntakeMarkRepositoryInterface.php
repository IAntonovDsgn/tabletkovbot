<?php

namespace App\Domain\IntakeMark;

interface IntakeMarkRepositoryInterface
{
    public function save(IntakeMark $intakeMark): void;

    public function findById(int $id): ?IntakeMark;

    /**
     * @return IntakeMark[]
     */
    public function findByUserId(int $userId): array;
}
