<?php

declare(strict_types=1);

namespace App\Infrastructure\Dbal\Repositories;

use App\Application\Services\OutboxService\ReportOutboxRepositoryInterface;
use App\Domain\Entities\Report\Report;
use App\Domain\Exceptions\NotFoundEntityException;
use App\Infrastructure\Dbal\Exceptions\AlreadyExistInPersistenceException;
use App\Infrastructure\Dbal\Exceptions\NotExistInPersistenceException;
use App\Infrastructure\Dbal\Exceptions\RepositoryException;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ReportOutboxRepository implements ReportOutboxRepositoryInterface
{
    use HydrateRowsTrait;

    const string REPORT_OUTBOX_TABLE_NAME = 'report_outbox';
    const string ID_COLUMN_NAME = 'id';
    const string CHAT_ID_COLUMN_NAME = 'chat_id';
    const string START_DATE_COLUMN_NAME = 'start_date';
    const string ATTEMPTS_COLUMN_NAME = 'attempts';

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws AlreadyExistInPersistenceException
     * @throws RepositoryException
     */
    public function insert(Report $report): void
    {
        if ($report->isExistInPersistence()) {
            throw new AlreadyExistInPersistenceException('isExistInPersistence = true');
        }

        $data = [
            self::CHAT_ID_COLUMN_NAME => $report->getChatId(),
            self::START_DATE_COLUMN_NAME => $report->getStartDate()->format(Report::DATE_FORMAT),
            self::ATTEMPTS_COLUMN_NAME => $report->getAttempts(),
        ];

        try {
            $this->connection->insert(
                self::REPORT_OUTBOX_TABLE_NAME,
                $data)
            ;
        } catch (\Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws NotExistInPersistenceException
     * @throws RepositoryException
     */
    public function update(Report $report): void
    {
        if (! $report->isExistInPersistence()) {
            throw new NotExistInPersistenceException('notExistInPersistence = false');
        }

        $data = [
            self::CHAT_ID_COLUMN_NAME => $report->getChatId(),
            self::START_DATE_COLUMN_NAME => $report->getStartDate()->format(Report::DATE_FORMAT),
            self::ATTEMPTS_COLUMN_NAME => $report->getAttempts(),
        ];

        try {
            $this->connection->update(
                self::REPORT_OUTBOX_TABLE_NAME,
                $data,
                [self::ID_COLUMN_NAME => $report->getId()]
            );
        } catch (\Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function getReports(int $limit): array
    {
        $result = [];
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::REPORT_OUTBOX_TABLE_NAME)
            ->orderBy(self::ID_COLUMN_NAME, 'ASC')
            ->setMaxResults($limit);

        try {
            $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $result[] = $this->mapOrmToDomain($row);
            }
        }

        return $result;
    }

    /**
     * @throws NotFoundEntityException
     * @throws RepositoryException
     * @throws NotExistInPersistenceException
     */
    public function markAttempt(Report $report): int
    {
        if (!$report->isExistInPersistence()) {
            throw new NotFoundEntityException('isExistInPersistence = false');
        }

        $attempts = $report->getAttempts();
        $report->setAttempts($attempts + 1);
        $this->update($report);

        return $report->getAttempts();
    }

    /**
     * @throws NotFoundEntityException
     * @throws RepositoryException
     */
    public function delete(Report $report): void
    {
        if (!$report->isExistInPersistence()) {
            throw new NotFoundEntityException('isExistInPersistence = false');
        }

        try {
            $this->connection->delete(
                self::REPORT_OUTBOX_TABLE_NAME,
                [self::ID_COLUMN_NAME => $report->getId()]
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    private function mapOrmToDomain(array $row): Report
    {
        return Report::restoreFromPersistence(
            $this->toInt($row[self::ID_COLUMN_NAME]),
            $this->toInt($row[self::CHAT_ID_COLUMN_NAME]),
            DateTimeImmutable::createFromFormat(
                Report::DATE_FORMAT,
                $this->toString($row[self::START_DATE_COLUMN_NAME])
            ),
            $this->toInt($row[self::ATTEMPTS_COLUMN_NAME]),
        );
    }
}
