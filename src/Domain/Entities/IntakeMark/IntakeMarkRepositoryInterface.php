<?php

declare(strict_types=1);

namespace App\Domain\Entities\IntakeMark;

use Doctrine\DBAL\Exception;

interface IntakeMarkRepositoryInterface
{
    public function insert(IntakeMark $intakeMark): int;

    public function update(IntakeMark $intakeMark): void;

    public function findById(int $id): ?IntakeMark;

    /**
     * @return IntakeMark[]
     */
    public function findByChatId(int $chatId): array;

    /**
     * @throws Exception
     */
    public function existsByChatId(int $chatId): bool;

    /**
     * @return IntakeMark[]
     */
    public function findForMonthByChatId(int $chatId, int $year, int $month): array;

    /**
     * @throws Exception
     */
    public function existsForTodayByMedicamentId(int $medicamentId): bool;
}
