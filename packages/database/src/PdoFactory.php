<?php
declare(strict_types=1);

namespace Velo\Database;

use PDO;

class PdoFactory
{
    public static function create(
        string $sql,
        string $dbname,
        string $host = '',
        string $username = '',
        string $password = '',
        int    $pdoErrorMode = PDO::ERRMODE_EXCEPTION,
        int    $pdoDefaultFetchMode = PDO::FETCH_ASSOC,
        bool   $pdoEmulatedPrepares = false
    ): PDO
    {
        if (strtolower($sql) === 'sqlite')
            $dsn = "sqlite:$dbname";
        else
            $dsn = "$sql:host=$host;dbname=$dbname;charset=utf8mb4";

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => $pdoErrorMode,
            PDO::ATTR_DEFAULT_FETCH_MODE => $pdoDefaultFetchMode,
            PDO::ATTR_EMULATE_PREPARES => $pdoEmulatedPrepares
        ]);
    }
}