<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use RestApi\Database\Database;

class DatabaseTest extends TestCase
{
    private Database $db;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/test_' . uniqid() . '.db';
        $this->db = Database::sqlite($this->dbPath);
        
        // Create test table
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS test_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL
            )
        ");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testQuery(): void
    {
        $this->db->execute("INSERT INTO test_users (name, email) VALUES (?, ?)", ['John', 'john@test.com']);
        
        $results = $this->db->query("SELECT * FROM test_users WHERE email = ?", ['john@test.com']);
        
        $this->assertCount(1, $results);
        $this->assertEquals('John', $results[0]['name']);
        $this->assertEquals('john@test.com', $results[0]['email']);
    }

    public function testQueryOne(): void
    {
        $this->db->execute("INSERT INTO test_users (name, email) VALUES (?, ?)", ['Jane', 'jane@test.com']);
        
        $result = $this->db->queryOne("SELECT * FROM test_users WHERE email = ?", ['jane@test.com']);
        
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertEquals('Jane', $result['name']);
    }

    public function testQueryOneReturnsNull(): void
    {
        $result = $this->db->queryOne("SELECT * FROM test_users WHERE email = ?", ['nonexistent@test.com']);
        
        $this->assertNull($result);
    }

    public function testExecute(): void
    {
        $rows = $this->db->execute("INSERT INTO test_users (name, email) VALUES (?, ?)", ['Test', 'test@test.com']);
        
        $this->assertEquals(1, $rows);
    }

    public function testLastInsertId(): void
    {
        $this->db->execute("INSERT INTO test_users (name, email) VALUES (?, ?)", ['Test', 'test@test.com']);
        
        $id = $this->db->lastInsertId();
        
        $this->assertIsString($id);
        $this->assertGreaterThan('0', $id);
    }

    public function testTransaction(): void
    {
        $this->db->beginTransaction();
        
        $this->db->execute("INSERT INTO test_users (name, email) VALUES (?, ?)", ['Test1', 'test1@test.com']);
        $this->db->execute("INSERT INTO test_users (name, email) VALUES (?, ?)", ['Test2', 'test2@test.com']);
        
        $this->db->commit();
        
        $results = $this->db->query("SELECT * FROM test_users");
        $this->assertCount(2, $results);
    }

    public function testTransactionRollback(): void
    {
        $this->db->beginTransaction();
        
        $this->db->execute("INSERT INTO test_users (name, email) VALUES (?, ?)", ['Test1', 'test1@test.com']);
        
        $this->db->rollback();
        
        $results = $this->db->query("SELECT * FROM test_users");
        $this->assertCount(0, $results);
    }

    public function testPreparedStatementPreventsInjection(): void
    {
        // Attempt SQL injection
        $malicious = "'; DROP TABLE test_users; --";
        
        $this->db->execute("INSERT INTO test_users (name, email) VALUES (?, ?)", ['Test', $malicious]);
        
        // Table should still exist
        $results = $this->db->query("SELECT * FROM test_users");
        $this->assertIsArray($results);
    }
}
