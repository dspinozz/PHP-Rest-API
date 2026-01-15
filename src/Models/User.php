<?php

declare(strict_types=1);

namespace RestApi\Models;

/**
 * User model
 * 
 * Represents a user entity with type-safe properties
 */
class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $createdAt
    ) {}

    /**
     * Create User from database array
     * 
     * @param array<string, mixed> $data Database row
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['id']) || !isset($data['email']) || !isset($data['password_hash'])) {
            throw new \InvalidArgumentException('Missing required user fields: id, email, password_hash');
        }
        
        return new self(
            id: (int)$data['id'],
            email: (string)$data['email'],
            passwordHash: (string)$data['password_hash'],
            createdAt: (string)($data['created_at'] ?? date('Y-m-d H:i:s'))
        );
    }

    /**
     * Convert to array (for API responses)
     * Excludes sensitive data like password_hash
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Convert to array with all fields (for database operations)
     * Includes sensitive data
     * 
     * @return array<string, mixed>
     */
    public function toDatabaseArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password_hash' => $this->passwordHash,
            'created_at' => $this->createdAt
        ];
    }
}
