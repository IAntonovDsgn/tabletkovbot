<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Repositories;

use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Exceptions\Interior\EntityAlreadyExistInPersistenceException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class IntakeMarkRepository implements IntakeMarkRepositoryInterface
{
    use HydrateRowsTrait;

    const string INTAKE_MARKS_TABLE_NAME = 'intake_marks';
    const string CHAT_ID_COLUMN_NAME = 'chat_id';
    const string MEDICAMENT_ID_COLUMN_NAME = 'medicament_id';
    const string IS_ACTIVE_COLUMN_NAME = 'is_active';
    const string CREATED_AT_COLUMN_NAME = 'created_at';
    const string ID_COLUMN_NAME = 'id';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function insert(IntakeMark $intakeMark): void
    {
        if ($intakeMark->isExistInPersistence()) {
            throw new EntityAlreadyExistInPersistenceException('IntakeMark isExistInPersistence = true');
        }

        $data = [
            self::CHAT_ID_COLUMN_NAME => $intakeMark->getChatId(),
            self::MEDICAMENT_ID_COLUMN_NAME => $intakeMark->getMedicamentId(),
            self::IS_ACTIVE_COLUMN_NAME => $intakeMark->isActive() ? 1 : 0,
            self::CREATED_AT_COLUMN_NAME => $intakeMark->getCreatedAt()->format(IntakeMark::DATE_TIME_FORMAT),
        ];

        $this->connection->insert(
            self::INTAKE_MARKS_TABLE_NAME,
            $data
        );
    }

    public function update(IntakeMark $intakeMark): void
    {
        if (!$intakeMark->isExistInPersistence()) {
            throw new NotFoundEntityException('IntakeMark isExistInPersistence = false');
        }

        $data = [
            self::CHAT_ID_COLUMN_NAME => $intakeMark->getChatId(),
            self::MEDICAMENT_ID_COLUMN_NAME => $intakeMark->getMedicamentId(),
            self::IS_ACTIVE_COLUMN_NAME => $intakeMark->isActive() ? 1 : 0,
            self::CREATED_AT_COLUMN_NAME => $intakeMark->getCreatedAt()->format(IntakeMark::DATE_TIME_FORMAT),
        ];

        $this->connection->update(
            self::INTAKE_MARKS_TABLE_NAME,
            $data,
            [self::ID_COLUMN_NAME => $intakeMark->getId()]
        );
    }

    /**
     * @throws Exception
     */
    public function findById(int $id): ?IntakeMark
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::INTAKE_MARKS_TABLE_NAME)
            ->where(self::ID_COLUMN_NAME . ' = :id')
            ->setParameter('id', $id);

        $row = $queryBuilder->executeQuery()->fetchAssociative();

        if ($row === false) {
            $result = null;
        } else {
            $result = $this->mapOrmToDomain($row);
        }

        return $result;
    }

    /**
     * @return IntakeMark[]
     *
     * @throws Exception
     */
    public function findByChatId(int $chatId): array
    {
        $result = [];
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::INTAKE_MARKS_TABLE_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME . ' = :chat_id')
            ->setParameter('chat_id', $chatId);

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        foreach ($rows as $row) {
            $result[] = $this->mapOrmToDomain($row);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapOrmToDomain(array $row): IntakeMark
    {
        return IntakeMark::restoreFromPersistence(
            $this->toInt($row[self::ID_COLUMN_NAME]),
            $this->toInt($row[self::CHAT_ID_COLUMN_NAME]),
            $this->toInt($row[self::MEDICAMENT_ID_COLUMN_NAME]),
            DateTimeImmutable::createFromFormat(
                IntakeMark::DATE_TIME_FORMAT,
                $this->toString($row[self::CREATED_AT_COLUMN_NAME])
            ) ?: new DateTimeImmutable(),
            $this->toBool($row[self::IS_ACTIVE_COLUMN_NAME])
        );
    }
}
