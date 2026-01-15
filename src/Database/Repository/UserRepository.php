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
     * Update user email
     * 
     * @param int $id User ID
     * @param string $email New email address
     * @return User Updated user
     * @throws \InvalidArgumentException If email is invalid
     * @throws \RuntimeException If user not found or email already taken
     */
    public function updateEmail(int $id, string $email): User
    {
        // Check user exists
        $user = $this->findById($id);
        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        // Check email not already taken by another user
        $existingUser = $this->findByEmail($email);
        if ($existingUser !== null && $existingUser->id !== $id) {
            throw new \RuntimeException('Email already in use by another user');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Update user
        $this->db->execute(
            'UPDATE ' . UserSchema::TABLE_NAME . ' SET email = ? WHERE id = ?',
            [$email, $id]
        );

        // Return updated user
        $updatedUser = $this->findById($id);
        if ($updatedUser === null) {
            throw new \RuntimeException('Failed to retrieve updated user');
        }

        return $updatedUser;
    }

    /**
     * Update user password
     * 
     * @param int $id User ID
     * @param string $passwordHash New password hash
     * @return User Updated user
     * @throws \RuntimeException If user not found
     */
    public function updatePassword(int $id, string $passwordHash): User
    {
        // Check user exists
        $user = $this->findById($id);
        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        // Update password
        $this->db->execute(
            'UPDATE ' . UserSchema::TABLE_NAME . ' SET password_hash = ? WHERE id = ?',
            [$passwordHash, $id]
        );

        // Return updated user
        $updatedUser = $this->findById($id);
        if ($updatedUser === null) {
            throw new \RuntimeException('Failed to retrieve updated user');
        }

        return $updatedUser;
    }

    /**
     * Update user (email and/or password)
     * 
     * @param int $id User ID
     * @param array<string, mixed> $data Fields to update (email, password_hash)
     * @return User Updated user
     * @throws \InvalidArgumentException If no valid fields provided
     * @throws \RuntimeException If user not found
     */
    public function update(int $id, array $data): User
    {
        // Check user exists
        $user = $this->findById($id);
        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        $updates = [];
        $params = [];

        // Handle email update
        if (isset($data['email'])) {
            $email = $data['email'];
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Invalid email format');
            }

            // Check email not already taken
            $existingUser = $this->findByEmail($email);
            if ($existingUser !== null && $existingUser->id !== $id) {
                throw new \RuntimeException('Email already in use by another user');
            }

            $updates[] = 'email = ?';
            $params[] = $email;
        }

        // Handle password update
        if (isset($data['password_hash'])) {
            $updates[] = 'password_hash = ?';
            $params[] = $data['password_hash'];
        }

        if (empty($updates)) {
            throw new \InvalidArgumentException('No valid fields to update');
        }

        // Add ID to params
        $params[] = $id;

        // Execute update
        $sql = 'UPDATE ' . UserSchema::TABLE_NAME . ' SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $this->db->execute($sql, $params);

        // Return updated user
        $updatedUser = $this->findById($id);
        if ($updatedUser === null) {
            throw new \RuntimeException('Failed to retrieve updated user');
        }

        return $updatedUser;
    }

    /**
     * Delete user by ID
     * 
     * @param int $id User ID
     * @return bool True if user was deleted
     * @throws \RuntimeException If user not found
     */
    public function delete(int $id): bool
    {
        // Check user exists
        $user = $this->findById($id);
        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        // Delete user
        $this->db->execute(
            'DELETE FROM ' . UserSchema::TABLE_NAME . ' WHERE id = ?',
            [$id]
        );

        return true;
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
     * Count total users
     * 
     * @return int Total number of users
     */
    public function count(): int
    {
        $result = $this->db->queryOne(
            'SELECT COUNT(*) as count FROM ' . UserSchema::TABLE_NAME
        );
        
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Check if user exists by ID
     * 
     * @param int $id User ID
     * @return bool True if user exists
     */
    public function exists(int $id): bool
    {
        return $this->findById($id) !== null;
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
