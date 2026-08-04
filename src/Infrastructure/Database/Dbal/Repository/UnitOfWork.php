<?php

namespace App\Infrastructure\Database\Dbal\Repository;

use App\Application\Persistence\UnitOfWorkInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class UnitOfWork implements UnitOfWorkInterface
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @throws Exception
     */
    public function begin(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * @throws Exception
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * @throws Exception
     */
    public function rollback(): void
    {
        $this->connection->rollBack();
    }
}
