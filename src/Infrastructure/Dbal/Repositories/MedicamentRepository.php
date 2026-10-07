<?php

declare(strict_types=1);

namespace App\Infrastructure\Dbal\Repositories;

use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Exceptions\NotFoundEntityException;
use App\Domain\Support\DateFormats;
use App\Infrastructure\Dbal\Exceptions\AlreadyExistInPersistenceException;
use App\Infrastructure\Dbal\Exceptions\RepositoryException;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Exception;

final readonly class MedicamentRepository implements MedicamentRepositoryInterface
{
    use HydrateRowsTrait;

    public const string MEDICAMENT_TABLE_NAME = 'medicaments';
    public const string NAME_COLUMN_NAME = 'name';
    public const string CHAT_ID_COLUMN_NAME = 'chat_id';
    public const string NOTIFICATION_TIME_COLUMN_NAME = 'notification_time';
    public const string IS_ACTIVE_COLUMN_NAME = 'is_active';
    public const string LAST_NOTIFICATION_DATE_COLUMN_NAME = 'last_notification_date';
    public const string ID_COLUMN_NAME = 'id';

    public function __construct(
        private Connection $connection,
    ) {}

    public function insert(Medicament $medicament): int
    {
        if ($medicament->isExistInPersistence()) {
            throw new AlreadyExistInPersistenceException(EnumMessageText::MEDICAMENT_ALREADY_EXIST->value);
        }

        if ($this->findByChatIdAndMedicamentName($medicament->getName(), $medicament->getChatId()) !== null) {
            throw new AlreadyExistInPersistenceException(EnumMessageText::MEDICAMENT_ALREADY_EXIST->value);
        }

        $data = [
            self::NAME_COLUMN_NAME => $medicament->getName(),
            self::CHAT_ID_COLUMN_NAME => $medicament->getChatId(),
            self::NOTIFICATION_TIME_COLUMN_NAME => $medicament->getNotificationTime()
                ?->format(DateFormats::TIME),
            self::IS_ACTIVE_COLUMN_NAME => $medicament->isActive() ? 1 : 0,
            self::LAST_NOTIFICATION_DATE_COLUMN_NAME => $medicament->getLastNotificationDate()
                ?->format(DateFormats::DATE),
        ];

        try {
            $this->connection->insert(self::MEDICAMENT_TABLE_NAME, $data);
            return (int) $this->connection->lastInsertId();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    public function update(Medicament $medicament): void
    {
        if (!$medicament->isExistInPersistence()) {
            throw new NotFoundEntityException('isExistInPersistence = false');
        }

        if ($this->findByChatIdAndMedicamentName($medicament->getName(), $medicament->getChatId()) !== null) {
            throw new AlreadyExistInPersistenceException(EnumMessageText::MEDICAMENT_ALREADY_EXIST->value);
        }

        $data = [
            self::NAME_COLUMN_NAME => $medicament->getName(),
            self::CHAT_ID_COLUMN_NAME => $medicament->getChatId(),
            self::NOTIFICATION_TIME_COLUMN_NAME => $medicament->getNotificationTime()
                ?->format(DateFormats::TIME),
            self::IS_ACTIVE_COLUMN_NAME => $medicament->isActive() ? 1 : 0,
            self::LAST_NOTIFICATION_DATE_COLUMN_NAME => $medicament->getLastNotificationDate()
                ?->format(DateFormats::DATE),
        ];

        try {
            $this->connection->update(
                self::MEDICAMENT_TABLE_NAME,
                $data,
                [self::ID_COLUMN_NAME => $medicament->getId()]
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

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
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }

        if ($row === false) {
            $result = null;
        } else {
            $result = $this->mapOrmToDomain($row);
        }

        return $result;
    }

    public function findById(int $id): ?Medicament
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MEDICAMENT_TABLE_NAME)
            ->where(self::ID_COLUMN_NAME . ' = :id')
            ->setParameter('id', $id);

        try {
            $row = $queryBuilder->executeQuery()->fetchAssociative();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }

        if ($row === false) {
            $result = null;
        } else {
            $result = $this->mapOrmToDomain($row);
        }

        return $result;
    }

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
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }

        foreach ($rows as $row) {
            $result[] = $this->mapOrmToDomain($row);
        }

        return $result;
    }

    public function findForNotificationNow(): array
    {
        $result = [];
        $now = new DateTimeImmutable('now', new DateTimeZone(DateFormats::TIME_ZONE));
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('m.*')
            ->from(self::MEDICAMENT_TABLE_NAME, 'm')
            ->leftJoin(
                'm',
                SessionRepository::SESSION_TABLE_NAME,
                's',
                's.' . SessionRepository::CHAT_ID_COLUMN_NAME . ' = m.' . self::CHAT_ID_COLUMN_NAME
            )
            ->where('m.' . self::IS_ACTIVE_COLUMN_NAME . ' = 1')
            ->andWhere('m.' . self::NOTIFICATION_TIME_COLUMN_NAME . ' IS NOT NULL')
            ->andWhere('m.' . self::NOTIFICATION_TIME_COLUMN_NAME . ' <= :nowTime')
            ->andWhere(
                '(' . 'm.' . self::LAST_NOTIFICATION_DATE_COLUMN_NAME . ' IS NULL'
                . ' OR ' . 'm.' . self::LAST_NOTIFICATION_DATE_COLUMN_NAME . ' <> :date)'
            )
            ->andWhere(
                '(s.' . SessionRepository::ID_COLUMN_NAME . ' IS NULL'
                . ' OR s.' . SessionRepository::IS_NOTIFICATION_ENABLED_COLUMN_NAME . ' = 1)'
            )
            ->setParameter('nowTime', $now->format(DateFormats::TIME))
            ->setParameter('date', $now->format(DateFormats::DATE));

        try {
            $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
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
                '!' . DateFormats::TIME,
                $notificationTimeString,
                new DateTimeZone(DateFormats::TIME_ZONE)
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

        $lastNotificationDateString = $this->toStringOrNull($row[self::LAST_NOTIFICATION_DATE_COLUMN_NAME]);

        if ($lastNotificationDateString === null) {
            $lastNotificationDate = null;
        } else {
            $lastNotificationDate = DateTimeImmutable::createFromFormat(
                '!' . DateFormats::DATE,
                $lastNotificationDateString,
                new DateTimeZone(DateFormats::TIME_ZONE)
            );

            if ($lastNotificationDate === false) {
                throw new RepositoryException(
                    sprintf(
                        'Unexpected last_notification_date value "%s" in %s.%d',
                        $lastNotificationDateString,
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
            $lastNotificationDate,
        );
    }
}
