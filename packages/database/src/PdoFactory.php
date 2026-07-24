<?php
declare(strict_types=1);

namespace Velo\Database;

use PDO;
use SensitiveParameter;

class PdoFactory
{
    public static function create(
        string                       $host = 'localhost',
        string                       $sql = 'mysql',
        int                          $port = 3306,
        string                       $dbname = '',
        string                       $username = '',
        #[SensitiveParameter] string $password = '',
        int                          $pdoErrorMode = PDO::ERRMODE_EXCEPTION,
        int                          $pdoDefaultFetchMode = PDO::FETCH_ASSOC,
        bool                         $pdoEmulatedPrepares = false
    ): PDO
    {
        if (strtolower($sql) === 'sqlite') {
            $dsn = "sqlite:$dbname";
        } else {
            $dsn = "$sql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        }

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => $pdoErrorMode,
            PDO::ATTR_DEFAULT_FETCH_MODE => $pdoDefaultFetchMode,
            PDO::ATTR_EMULATE_PREPARES => $pdoEmulatedPrepares
        ]);
    }
}