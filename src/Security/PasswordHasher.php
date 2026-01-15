<?php

declare(strict_types=1);

namespace RestApi\Security;

/**
 * Password hashing and verification
 * Uses PHP's built-in password_hash with current best practices
 */
class PasswordHasher
{
    /**
     * Hash a password
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against a hash
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehashing (algorithm updated)
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Validate password strength
     */
    public static function validateStrength(string $password, int $minLength = 8): array
    {
        $errors = [];

        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        return $errors;
    }
}
