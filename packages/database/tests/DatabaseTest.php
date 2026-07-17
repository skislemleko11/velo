<?php
declare(strict_types=1);

namespace Velo\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Core\Database;
use Velo\Core\PdoFactory;
use PDO;

class DatabaseTest extends TestCase
{
    protected Database $db;
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = PdoFactory::create(
            sql: 'sqlite',
            dbname: ':memory:'
        );
        $this->db = new Database($this->pdo);

        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL
            )
        ");

        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('Jan Kowalski', 'jan@wp.pl')");
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('Anna Nowak', 'anna@o2.pl')");
    }

    #[Test]
    public function it_returns_the_same_pdo(): void
    {
        $this->assertSame($this->pdo, $this->db->getPDO());
    }

    public function testFetchOneReturnsSingleRow(): void
    {
        $result = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email",
            ['email' => 'jan@wp.pl']
        );

        $this->assertIsArray($result);
        $this->assertSame('Jan Kowalski', $result['name']);
        $this->assertEquals(1, $result['id']);
    }

    public function testFetchOneReturnsEmptyArrayWhenNoResults(): void
    {
        $result = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email",
            ['email' => 'nieistnieje@test.pl']
        );

        $this->assertSame([], $result);
    }

    public function testFetchAllReturnsMultipleRows(): void
    {
        $result = $this->db->fetchAll("SELECT * FROM users ORDER BY id ASC");

        $this->assertCount(2, $result);
        $this->assertSame('Jan Kowalski', $result[0]['name']);
        $this->assertSame('Anna Nowak', $result[1]['name']);
    }

    public function testExecuteCanInsertDataAndReturnRowCount(): void
    {
        $rowCount = $this->db->execute(
            "INSERT INTO users (name, email) VALUES (:name, :email)",
            ['name' => 'Tomasz Bat', 'email' => 'tomasz@test.pl'],
            returnRowCount: true
        );

        $this->assertSame(1, $rowCount);

        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = 'tomasz@test.pl'");
        $this->assertSame('Tomasz Bat', $user['name']);
    }

    public function testTransactionsRollbackChangesOnError(): void
    {
        $this->db->beginTransaction();

        $this->db->execute(
            "INSERT INTO users (name, email) VALUES (:name, :email)",
            ['name' => 'Błąd Transakcji', 'email' => 'error@test.pl']
        );

        $this->db->rollback();

        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = 'error@test.pl'");
        $this->assertSame([], $user);
    }

    public function testTransactionsCommitChangesOnSuccess(): void
    {
        $this->db->beginTransaction();

        $this->db->execute(
            "INSERT INTO users (name, email) VALUES (:name, :email)",
            ['name' => 'Sukces Transakcji', 'email' => 'success@test.pl']
        );

        $this->db->commit();

        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = 'success@test.pl'");
        $this->assertSame('Sukces Transakcji', $user['name']);
    }
}