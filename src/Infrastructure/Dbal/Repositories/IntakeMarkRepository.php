<?php

declare(strict_types=1);

namespace App\Infrastructure\Dbal\Repositories;

use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Exceptions\NotFoundEntityException;
use App\Infrastructure\Dbal\Exceptions\AlreadyExistInPersistenceException;
use App\Infrastructure\Dbal\Exceptions\RepositoryException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class IntakeMarkRepository implements IntakeMarkRepositoryInterface
{
    use HydrateRowsTrait;

    public const string INTAKE_MARKS_TABLE_NAME = 'intake_marks';
    public const string CHAT_ID_COLUMN_NAME = 'chat_id';
    public const string MEDICAMENT_ID_COLUMN_NAME = 'medicament_id';
    public const string IS_ACTIVE_COLUMN_NAME = 'is_active';
    public const string CREATED_AT_COLUMN_NAME = 'created_at';
    public const string ID_COLUMN_NAME = 'id';

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws AlreadyExistInPersistenceException
     * @throws RepositoryException
     */
    public function insert(IntakeMark $intakeMark): int
    {
        if ($intakeMark->isExistInPersistence()) {
            throw new AlreadyExistInPersistenceException('IntakeMark isExistInPersistence = true');
        }

        $data = [
            self::CHAT_ID_COLUMN_NAME => $intakeMark->getChatId(),
            self::MEDICAMENT_ID_COLUMN_NAME => $intakeMark->getMedicamentId(),
            self::IS_ACTIVE_COLUMN_NAME => $intakeMark->isActive() ? 1 : 0,
            self::CREATED_AT_COLUMN_NAME => $intakeMark->getCreatedAt()->format(IntakeMark::DATE_TIME_FORMAT),
        ];

        try {
            $this->connection->insert(self::INTAKE_MARKS_TABLE_NAME, $data);
            return (int) $this->connection->lastInsertId();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws RepositoryException
     * @throws NotFoundEntityException
     */
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

        try {
            $this->connection->update(
                self::INTAKE_MARKS_TABLE_NAME,
                $data,
                [self::ID_COLUMN_NAME => $intakeMark->getId()]
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
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
     * @throws Exception
     */
    public function existsByChatId(int $chatId): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select(self::ID_COLUMN_NAME)
            ->from(self::INTAKE_MARKS_TABLE_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME . ' = :chat_id')
            ->setParameter('chat_id', $chatId)
            ->setMaxResults(1);

        return $queryBuilder->executeQuery()->fetchOne() !== false;
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function findForMonthByChatId(int $chatId, int $year, int $month): array
    {
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $end = $start->modify('first day of next month');
        $queryBuilder = $this->connection->createQueryBuilder();
        $result = [];

        $queryBuilder->select('*')
            ->from(self::INTAKE_MARKS_TABLE_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME . ' = :chat_id')
            ->andWhere(self::CREATED_AT_COLUMN_NAME . ' >= :start')
            ->andWhere(self::CREATED_AT_COLUMN_NAME . ' < :end')
            ->setParameter('chat_id', $chatId)
            ->setParameter('start', $start->format(IntakeMark::DATE_TIME_FORMAT))
            ->setParameter('end', $end->format(IntakeMark::DATE_TIME_FORMAT));

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
