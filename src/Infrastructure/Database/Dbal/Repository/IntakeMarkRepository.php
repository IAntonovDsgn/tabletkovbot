<?php

namespace App\Infrastructure\Database\Dbal\Repository;

use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class IntakeMarkRepository implements IntakeMarkRepositoryInterface
{
    const string TABLE_INTAKE_MARKS_NAME = 'intake_marks';
    const string CHAT_ID_COLUMN_NAME = 'chat_id';
    const string MEDICAMENT_ID_COLUMN_NAME = 'medicament_id';
    const string IS_ACTIVE_COLUMN_NAME = 'is_active';
    const string CREATED_AT_COLUMN_NAME = 'created_at';
    const string ID_COLUMN_NAME = 'id';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @throws Exception
     */
    public function save(IntakeMark $intakeMark): void
    {
        $data = [
            self::CHAT_ID_COLUMN_NAME => $intakeMark->getChatId(),
            self::MEDICAMENT_ID_COLUMN_NAME => $intakeMark->getMedicamentId(),
            self::IS_ACTIVE_COLUMN_NAME => $intakeMark->isActive() ? 1 : 0,
            self::CREATED_AT_COLUMN_NAME => $intakeMark->getCreatedAt()->format(IntakeMark::DATE_TIME_FORMAT),
        ];

        if ($intakeMark->getId() !== null) {
            $this->connection->update(
                self::TABLE_INTAKE_MARKS_NAME,
                $data,
                [self::ID_COLUMN_NAME => $intakeMark->getId()]
            );
        } else {
            $this->connection->insert(self::TABLE_INTAKE_MARKS_NAME, $data);
        }
    }

    /**
     * @throws Exception
     * @throws DateMalformedStringException
     */
    public function findById(int $id): ?IntakeMark
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::TABLE_INTAKE_MARKS_NAME)
            ->where(self::ID_COLUMN_NAME.' = :id')
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
            ->from(self::TABLE_INTAKE_MARKS_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME.' = :chat_id')
            ->setParameter('chat_id', $chatId);

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        foreach ($rows as $row) {
            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    /**
     * @throws DateMalformedStringException
     */
    private function hydrate(array $row): IntakeMark
    {
        return new IntakeMark(
            (int) $row[self::CHAT_ID_COLUMN_NAME],
            (int) $row[self::MEDICAMENT_ID_COLUMN_NAME],
            new DateTimeImmutable($row[self::CREATED_AT_COLUMN_NAME], IntakeMark::DATE_TIME_FORMAT),
            (bool) $row[self::IS_ACTIVE_COLUMN_NAME],
            (int) $row[self::ID_COLUMN_NAME]
        );
    }
}
