<?php
declare(strict_types=1);

namespace Velo\Database\Tests;

use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Database\PdoFactory;

use PDO;

class PdoFactoryTest extends TestCase
{
    #[Test]
    public function it_can_create_sqlite_connection(): void
    {
        $pdo = PdoFactory::create(
            sql: 'sqlite',
            dbname: ':memory:',
            pdoErrorMode: PDO::ERRMODE_SILENT,
            pdoDefaultFetchMode: PDO::FETCH_OBJ
        );

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame(PDO::ERRMODE_SILENT, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertSame(PDO::FETCH_OBJ, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    #[Test]
    public function it_throws_exception_on_invalid_driver(): void
    {
        $this->expectException(PDOException::class);

        PdoFactory::create(
            sql: 'invalid_driver',
            dbname: 'test',
            host: 'localhost',
            username: 'root',
            password: 'a'
        );
    }
}