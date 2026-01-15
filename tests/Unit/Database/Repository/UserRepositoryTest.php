<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Database\Repository;

use PHPUnit\Framework\TestCase;
use RestApi\Database\Database;
use RestApi\Database\Repository\UserRepository;
use RestApi\Security\PasswordHasher;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;
    private Database $db;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/test_users_' . uniqid() . '.db';
        $this->db = Database::sqlite($this->dbPath);
        $this->repository = new UserRepository($this->db);
        
        // Initialize table
        $this->repository->initializeTable('sqlite');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testCreateUser(): void
    {
        $user = $this->repository->create(
            'test@example.com',
            PasswordHasher::hash('password123')
        );
        
        $this->assertNotNull($user);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertGreaterThan(0, $user->id);
    }

    public function testCreateUserDuplicateEmail(): void
    {
        $this->repository->create('test@example.com', PasswordHasher::hash('password123'));
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User with email already exists');
        
        $this->repository->create('test@example.com', PasswordHasher::hash('password456'));
    }

    public function testFindByEmail(): void
    {
        $created = $this->repository->create('test@example.com', PasswordHasher::hash('password123'));
        
        $found = $this->repository->findByEmail('test@example.com');
        
        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
        $this->assertEquals($created->email, $found->email);
    }

    public function testFindByEmailNotFound(): void
    {
        $found = $this->repository->findByEmail('nonexistent@example.com');
        
        $this->assertNull($found);
    }

    public function testFindById(): void
    {
        $created = $this->repository->create('test@example.com', PasswordHasher::hash('password123'));
        
        $found = $this->repository->findById($created->id);
        
        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
    }

    public function testFindAll(): void
    {
        $this->repository->create('user1@example.com', PasswordHasher::hash('pass1'));
        $this->repository->create('user2@example.com', PasswordHasher::hash('pass2'));
        
        $users = $this->repository->findAll();
        
        $this->assertCount(2, $users);
        $this->assertInstanceOf(\RestApi\Models\User::class, $users[0]);
    }

    public function testUserModelToArray(): void
    {
        $user = $this->repository->create('test@example.com', PasswordHasher::hash('password123'));
        
        $array = $user->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayNotHasKey('password_hash', $array); // Should not expose password
    }
}
