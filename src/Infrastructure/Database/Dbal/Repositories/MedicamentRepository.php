<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Repositories;

use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Exceptions\Interior\EntityAlreadyExistInPersistenceException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class MedicamentRepository implements MedicamentRepositoryInterface
{
    use HydrateRowsTrait;

    const string MEDICAMENT_TABLE_NAME = 'medicaments';
    const string NAME_COLUMN_NAME = 'name';
    const string CHAT_ID_COLUMN_NAME = 'chat_id';
    const string NOTIFICATION_TIME_COLUMN_NAME = 'notification_time';
    const string IS_ACTIVE_COLUMN_NAME = 'is_active';
    const string ID_COLUMN_NAME = 'id';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function insert(Medicament $medicament): int
    {
        if ($medicament->isExistInPersistence()) {
            throw new EntityAlreadyExistInPersistenceException('isExistInPersistence = true');
        }

        $data = [
            self::NAME_COLUMN_NAME => $medicament->getName(),
            self::CHAT_ID_COLUMN_NAME => $medicament->getChatId(),
            self::NOTIFICATION_TIME_COLUMN_NAME => $medicament->getNotificationTime()
                ?->format(Medicament::TIME_FORMAT . ':s'),
            self::IS_ACTIVE_COLUMN_NAME => $medicament->isActive(),
        ];

        try {
            $this->connection->insert(self::MEDICAMENT_TABLE_NAME, $data);
            return (int)$this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new RepositoryException($e->getMessage());
        }
    }

    public function update(Medicament $medicament): void
    {
        if (!$medicament->isExistInPersistence()) {
            throw new NotFoundEntityException('isExistInPersistence = false');
        }

        $data = [
            self::NAME_COLUMN_NAME => $medicament->getName(),
            self::CHAT_ID_COLUMN_NAME => $medicament->getChatId(),
            self::NOTIFICATION_TIME_COLUMN_NAME => $medicament->getNotificationTime()
                ?->format(Medicament::TIME_FORMAT . ':s'),
            self::IS_ACTIVE_COLUMN_NAME => $medicament->isActive(),
        ];

        try {
            $this->connection->update(
                self::MEDICAMENT_TABLE_NAME,
                $data,
                [self::ID_COLUMN_NAME => $medicament->getId()]
            );
        } catch (\Exception $e) {
            throw new RepositoryException($e->getMessage());
        }
    }

    /**
     * @throws RepositoryException
     */
    public function findByChatIdAndMedicamentName(string $medicamentName, int $chatId): ?Medicament
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MEDICAMENT_TABLE_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME . ' = :chatId')
            ->andWhere(self::NAME_COLUMN_NAME . ' = :medicamentName')
            ->setParameter('chatId', $chatId)
            ->setParameter('medicamentName', $medicamentName);

        try {
            $row = $queryBuilder->executeQuery()->fetchAssociative();
        } catch (\Exception $e) {
            throw new RepositoryException($e->getMessage());
        }

        if ($row === false) {
            $result = null;
        } else {
            $result = $this->mapOrmToDomain($row);
        }

        return $result;
    }

    /**
     * @throws RepositoryException
     */
    public function findById(int $id): ?Medicament
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MEDICAMENT_TABLE_NAME)
            ->where(self::ID_COLUMN_NAME . ' = :id')
            ->setParameter('id', $id);

        try {
            $row = $queryBuilder->executeQuery()->fetchAssociative();
        } catch (\Exception $e) {
            throw new RepositoryException($e->getMessage());
        }

        if ($row === false) {
            $result = null;
        } else {
            $result = $this->mapOrmToDomain($row);
        }

        return $result;
    }

    /**
     * @return Medicament[]
     * @throws RepositoryException
     */
    public function findByChatId(int $chatId): array
    {
        $result = [];
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MEDICAMENT_TABLE_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME . ' = :chatId')
            ->setParameter('chatId', $chatId);

        try {
            $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new RepositoryException($e->getMessage());
        }

        foreach ($rows as $row) {
            $result[] = $this->mapOrmToDomain($row);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     * @throws RepositoryException
     */
    private function mapOrmToDomain(array $row): Medicament
    {
        $notificationTimeString = $this->toStringOrNull($row[self::NOTIFICATION_TIME_COLUMN_NAME]);

        if ($notificationTimeString === null) {
            $notificationTime = null;
        } else {
            $notificationTime = DateTimeImmutable::createFromFormat(
                '!' . Medicament::TIME_FORMAT . ':s',
                $notificationTimeString,
                new DateTimeZone(Medicament::DATE_TIME_ZONE)
            );

            if ($notificationTime === false) {
                throw new RepositoryException(
                    sprintf(
                        'Unexpected notification_time value "%s" in %s.%d',
                        $notificationTimeString,
                        self::MEDICAMENT_TABLE_NAME,
                        $this->toInt($row[self::ID_COLUMN_NAME]),
                    )
                );
            }
        }

        return Medicament::restoreFromPersistence(
            $this->toInt($row[self::ID_COLUMN_NAME]),
            $this->toString($row[self::NAME_COLUMN_NAME]),
            $this->toInt($row[self::CHAT_ID_COLUMN_NAME]),
            $notificationTime,
            $this->toBool($row[self::IS_ACTIVE_COLUMN_NAME]),
        );
    }
}
