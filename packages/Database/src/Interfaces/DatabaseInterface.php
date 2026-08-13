<?php
declare(strict_types=1);

namespace Velo\Database\Interfaces;

use PDO, PDOStatement, Generator;

/**
 * Database Interface forcing basic features implementation.
 */
interface DatabaseInterface
{
    public function getPDO(): PDO;

    public function execute(string $query, array $params = [], bool $returnRowCount = false): ?int;

    public function prepare(string $query): PDOStatement;

    public function fetchOne(string $query, array $params = []): array;

    public function fetchAll(string $query, array $params = []): array;

    /**
     * Fetches all rows, uses Generator to reduce memory usage.
     *
     * @return Generator<int, array>
     */
    public function fetchAllLazy(string $query, array $params = []): Generator;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollback(): bool;

    public function getLastInsertId(?string $name = null): false|int;
}