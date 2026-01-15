<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RestApi\Models\User;

class UserTest extends TestCase
{
    public function testFromArray(): void
    {
        $data = [
            'id' => 123,
            'email' => 'test@example.com',
            'password_hash' => 'hashed_password',
            'created_at' => '2024-01-01 00:00:00'
        ];
        
        $user = User::fromArray($data);
        
        $this->assertEquals(123, $user->id);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('hashed_password', $user->passwordHash);
        $this->assertEquals('2024-01-01 00:00:00', $user->createdAt);
    }

    public function testFromArrayMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        User::fromArray(['id' => 123]); // Missing email and password_hash
    }

    public function testToArrayExcludesPassword(): void
    {
        $user = new User(
            id: 123,
            email: 'test@example.com',
            passwordHash: 'secret',
            createdAt: '2024-01-01 00:00:00'
        );
        
        $array = $user->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayNotHasKey('password_hash', $array);
    }

    public function testToDatabaseArrayIncludesPassword(): void
    {
        $user = new User(
            id: 123,
            email: 'test@example.com',
            passwordHash: 'secret',
            createdAt: '2024-01-01 00:00:00'
        );
        
        $array = $user->toDatabaseArray();
        
        $this->assertArrayHasKey('password_hash', $array);
        $this->assertEquals('secret', $array['password_hash']);
    }
}
