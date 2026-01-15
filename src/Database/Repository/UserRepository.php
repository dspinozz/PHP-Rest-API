<?php

declare(strict_types=1);

namespace RestApi\Database\Repository;

use RestApi\Database\Database;
use RestApi\Database\Schema\UserSchema;
use RestApi\Models\User;

/**
 * User Repository
 * 
 * Provides type-safe database operations for users
 * Ensures schema contracts are maintained
 */
class UserRepository
{
    public function __construct(
        private Database $db
    ) {}

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        $data = $this->db->queryOne(
            'SELECT * FROM ' . UserSchema::TABLE_NAME . ' WHERE id = ?',
            [$id]
        );
        
        return $data ? User::fromArray($data) : null;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        $data = $this->db->queryOne(
            'SELECT * FROM ' . UserSchema::TABLE_NAME . ' WHERE email = ?',
            [$email]
        );
        
        return $data ? User::fromArray($data) : null;
    }

    /**
     * Create a new user
     */
    public function create(string $email, string $passwordHash): User
    {
        // Validate schema
        $errors = UserSchema::validate([
            'email' => $email,
            'password_hash' => $passwordHash
        ]);
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid user data: ' . implode(', ', $errors));
        }

        // Check if user exists
        $existing = $this->findByEmail($email);
        if ($existing !== null) {
            throw new \RuntimeException('User with email already exists');
        }

        // Insert user
        $this->db->execute(
            'INSERT INTO ' . UserSchema::TABLE_NAME . ' (email, password_hash) VALUES (?, ?)',
            [$email, $passwordHash]
        );

        $userIdStr = $this->db->lastInsertId();
        $userId = (int)$userIdStr;
        
        if ($userId <= 0) {
            throw new \RuntimeException('Failed to get user ID after creation');
        }
        
        // Fetch created user
        $user = $this->findById($userId);
        if ($user === null) {
            throw new \RuntimeException('Failed to retrieve created user');
        }

        return $user;
    }

    /**
     * Get all users
     * 
     * @return array<int, User>
     */
    public function findAll(): array
    {
        $data = $this->db->query(
            'SELECT id, email, password_hash, created_at FROM ' . UserSchema::TABLE_NAME . ' ORDER BY created_at DESC'
        );
        
        return array_map(fn($row) => User::fromArray($row), $data);
    }

    /**
     * Initialize user table
     */
    public function initializeTable(string $dbType = 'sqlite'): void
    {
        $sql = match($dbType) {
            'mysql' => UserSchema::getCreateTableSqlMySQL(),
            'postgresql' => UserSchema::getCreateTableSqlPostgreSQL(),
            default => UserSchema::getCreateTableSql()
        };
        
        $this->db->execute($sql);
    }
}
