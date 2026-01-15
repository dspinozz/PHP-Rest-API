<?php

declare(strict_types=1);

namespace RestApi\Database\Schema;

/**
 * User table schema definition
 * 
 * Ensures consistent schema across database operations
 */
class UserSchema
{
    public const TABLE_NAME = 'users';
    
    public const COLUMNS = [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'email' => 'TEXT UNIQUE NOT NULL',
        'password_hash' => 'TEXT NOT NULL',
        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
    ];

    /**
     * Get CREATE TABLE SQL for SQLite
     */
    public static function getCreateTableSql(): string
    {
        $columns = [];
        foreach (self::COLUMNS as $name => $definition) {
            $columns[] = "$name $definition";
        }
        
        return sprintf(
            "CREATE TABLE IF NOT EXISTS %s (%s)",
            self::TABLE_NAME,
            implode(', ', $columns)
        );
    }

    /**
     * Get CREATE TABLE SQL for MySQL
     */
    public static function getCreateTableSqlMySQL(): string
    {
        return sprintf(
            "CREATE TABLE IF NOT EXISTS %s (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            self::TABLE_NAME
        );
    }

    /**
     * Get CREATE TABLE SQL for PostgreSQL
     */
    public static function getCreateTableSqlPostgreSQL(): string
    {
        return sprintf(
            "CREATE TABLE IF NOT EXISTS %s (
                id SERIAL PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            self::TABLE_NAME
        );
    }

    /**
     * Validate user data matches schema
     */
    public static function validate(array $data): array
    {
        $errors = [];
        
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (isset($data['password_hash']) && empty($data['password_hash'])) {
            $errors[] = 'Password hash cannot be empty';
        }
        
        return $errors;
    }
}
