<?php
declare(strict_types=1);

namespace Velo\Database;

use PDO, PDOStatement;
use Velo\Database\Interfaces\DatabaseInterface;

readonly class Database implements DatabaseInterface
{
    public function __construct(protected PDO $pdo)
    {
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function prepare(string $query): PDOStatement
    {
        return $this->pdo->prepare($query);
    }

    public function execute(string $query, array $params = [], bool $returnRowCount = false): ?int
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);

        return $returnRowCount ? $stmt->rowCount() : null;
    }

    public function fetchOne(string $query, array $params = []): array
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);

        return $stmt->fetch() ?: [];
    }

    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
}