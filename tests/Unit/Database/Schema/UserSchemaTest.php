<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Database\Schema;

use PHPUnit\Framework\TestCase;
use RestApi\Database\Schema\UserSchema;

class UserSchemaTest extends TestCase
{
    public function testGetCreateTableSql(): void
    {
        $sql = UserSchema::getCreateTableSql();
        
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString(UserSchema::TABLE_NAME, $sql);
        $this->assertStringContainsString('id', $sql);
        $this->assertStringContainsString('email', $sql);
        $this->assertStringContainsString('password_hash', $sql);
    }

    public function testGetCreateTableSqlMySQL(): void
    {
        $sql = UserSchema::getCreateTableSqlMySQL();
        
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('VARCHAR', $sql);
    }

    public function testGetCreateTableSqlPostgreSQL(): void
    {
        $sql = UserSchema::getCreateTableSqlPostgreSQL();
        
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('SERIAL', $sql);
        $this->assertStringContainsString('VARCHAR', $sql);
    }

    public function testValidateValidData(): void
    {
        $errors = UserSchema::validate([
            'email' => 'test@example.com',
            'password_hash' => 'hashed_password'
        ]);
        
        $this->assertEmpty($errors);
    }

    public function testValidateInvalidEmail(): void
    {
        $errors = UserSchema::validate([
            'email' => 'invalid-email',
            'password_hash' => 'hashed_password'
        ]);
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid email format', $errors);
    }

    public function testValidateEmptyPasswordHash(): void
    {
        $errors = UserSchema::validate([
            'email' => 'test@example.com',
            'password_hash' => ''
        ]);
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Password hash cannot be empty', $errors);
    }
}
