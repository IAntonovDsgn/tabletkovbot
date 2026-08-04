<?php

namespace App\Infrastructure\Database\Dbal\Repository;

use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class MedicamentRepository implements MedicamentRepositoryInterface
{
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

    /**
     * @throws Exception
     */
    public function save(Medicament $medicament): void
    {
        $data = [
            self::NAME_COLUMN_NAME => $medicament->getName(),
            self::CHAT_ID_COLUMN_NAME => $medicament->getChatId(),
            self::NOTIFICATION_TIME_COLUMN_NAME => $medicament->getNotificationTime()->format(Medicament::TIME_FORMAT),
            self::IS_ACTIVE_COLUMN_NAME => $medicament->isActive(),
        ];

        if ($medicament->getId() !== null) {
            $this->connection->update(
                self::MEDICAMENT_TABLE_NAME,
                $data,
                [self::ID_COLUMN_NAME => $medicament->getId()]
            );
        } else {
            $this->connection->insert(self::MEDICAMENT_TABLE_NAME, $data);
        }
    }

    /**
     * @throws Exception
     * @throws DateMalformedStringException
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

        $row = $queryBuilder->executeQuery()->fetchAssociative();

        if ($row === false) {
            $result = null;
        } else {
            $result = $this->hydrate($row);
        }

        return $result;
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function findById(int $id): ?Medicament
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MEDICAMENT_TABLE_NAME)
            ->where(self::ID_COLUMN_NAME . ' = :id')
            ->setParameter('id', $id);

        $row = $queryBuilder->executeQuery()->fetchAssociative();

        if ($row === false) {
            $result = null;
        } else {
            $result = $this->hydrate($row);
        }

        return $result;
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function findByChatId(int $chatId): array
    {
        $result = [];
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MEDICAMENT_TABLE_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME . ' = :chatId')
            ->setParameter('chatId', $chatId);

        $rows = $queryBuilder->executeQuery()->fetchAssociative();

        foreach ($rows as $row) {
            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    /**
     * @throws DateMalformedStringException
     */
    private function hydrate(array $row): Medicament
    {
        return new Medicament(
            $row[self::NAME_COLUMN_NAME],
            (int)$row[self::CHAT_ID_COLUMN_NAME],
            new DateTimeImmutable($row[self::NOTIFICATION_TIME_COLUMN_NAME], Medicament::TIME_FORMAT),
            (bool)$row[self::IS_ACTIVE_COLUMN_NAME],
            (int)$row[self::ID_COLUMN_NAME]
        );
    }
}
