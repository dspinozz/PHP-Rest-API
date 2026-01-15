<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use RestApi\Security\PasswordHasher;

class PasswordHasherTest extends TestCase
{
    public function testHashPassword(): void
    {
        $password = 'test-password-123';
        $hash = PasswordHasher::hash($password);
        
        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
    }

    public function testVerifyPassword(): void
    {
        $password = 'test-password-123';
        $hash = PasswordHasher::hash($password);
        
        $this->assertTrue(PasswordHasher::verify($password, $hash));
        $this->assertFalse(PasswordHasher::verify('wrong-password', $hash));
    }

    public function testPasswordStrengthValidation(): void
    {
        $errors = PasswordHasher::validateStrength('weak');
        $this->assertNotEmpty($errors);
        
        $errors = PasswordHasher::validateStrength('StrongPass123');
        $this->assertEmpty($errors);
    }

    public function testPasswordStrengthRequirements(): void
    {
        // Too short
        $errors = PasswordHasher::validateStrength('Short1');
        $this->assertContains('at least 8 characters', implode(' ', $errors));
        
        // No uppercase
        $errors = PasswordHasher::validateStrength('lowercase123');
        $this->assertNotEmpty($errors);
        
        // No lowercase
        $errors = PasswordHasher::validateStrength('UPPERCASE123');
        $this->assertNotEmpty($errors);
        
        // No number
        $errors = PasswordHasher::validateStrength('NoNumber');
        $this->assertNotEmpty($errors);
    }

    public function testNeedsRehash(): void
    {
        // Modern hash shouldn't need rehashing
        $password = 'test-password-123';
        $hash = PasswordHasher::hash($password);
        
        $this->assertFalse(PasswordHasher::needsRehash($hash));
    }
}
