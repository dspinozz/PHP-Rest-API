<?php

declare(strict_types=1);

namespace RestApi\Tests\Integration;

use PHPUnit\Framework\TestCase;
use RestApi\Database\Database;
use RestApi\Database\Repository\UserRepository;
use RestApi\Security\PasswordHasher;

/**
 * Integration tests for database operations
 * Tests schema contracts and data integrity
 */
class DatabaseIntegrationTest extends TestCase
{
    private UserRepository $repository;
    private Database $db;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/integration_test_' . uniqid() . '.db';
        $this->db = Database::sqlite($this->dbPath);
        $this->repository = new UserRepository($this->db);
        $this->repository->initializeTable('sqlite');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testUserLifecycle(): void
    {
        // Create user
        $user = $this->repository->create(
            'test@example.com',
            PasswordHasher::hash('password123')
        );
        
        $this->assertGreaterThan(0, $user->id);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNotEmpty($user->passwordHash);
        $this->assertNotEmpty($user->createdAt);

        // Find by ID
        $foundById = $this->repository->findById($user->id);
        $this->assertNotNull($foundById);
        $this->assertEquals($user->id, $foundById->id);
        $this->assertEquals($user->email, $foundById->email);

        // Find by email
        $foundByEmail = $this->repository->findByEmail($user->email);
        $this->assertNotNull($foundByEmail);
        $this->assertEquals($user->id, $foundByEmail->id);

        // Verify password hash is stored
        $this->assertNotEquals('password123', $foundByEmail->passwordHash);
        $this->assertTrue(PasswordHasher::verify('password123', $foundByEmail->passwordHash));
    }

    public function testSchemaContract(): void
    {
        $user = $this->repository->create('test@example.com', PasswordHasher::hash('pass123'));
        
        // Verify model has all required fields
        $array = $user->toArray();
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('created_at', $array);
        
        // Verify password is NOT exposed in toArray()
        $this->assertArrayNotHasKey('password_hash', $array);
        
        // Verify password IS in database array
        $dbArray = $user->toDatabaseArray();
        $this->assertArrayHasKey('password_hash', $dbArray);
    }

    public function testMultipleUsers(): void
    {
        $user1 = $this->repository->create('user1@test.com', PasswordHasher::hash('pass1'));
        $user2 = $this->repository->create('user2@test.com', PasswordHasher::hash('pass2'));
        
        $all = $this->repository->findAll();
        
        $this->assertCount(2, $all);
        $this->assertNotEquals($user1->id, $user2->id);
    }
}
