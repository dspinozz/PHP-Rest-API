<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use RestApi\Validation\Validator;

class ValidatorTest extends TestCase
{
    public function testRequiredField(): void
    {
        $validator = new Validator([]);
        $validator->required('email');
        
        $this->assertFalse($validator->isValid());
        $this->assertNotEmpty($validator->getErrors());
    }

    public function testEmailValidation(): void
    {
        $validator = new Validator(['email' => 'invalid-email']);
        $validator->email('email');
        
        $this->assertFalse($validator->isValid());
    }

    public function testValidEmail(): void
    {
        $validator = new Validator(['email' => 'test@example.com']);
        $validator->email('email');
        
        $this->assertTrue($validator->isValid());
    }

    public function testMinLength(): void
    {
        $validator = new Validator(['password' => 'short']);
        $validator->minLength('password', 8);
        
        $this->assertFalse($validator->isValid());
    }

    public function testNumeric(): void
    {
        $validator = new Validator(['age' => 'not-a-number']);
        $validator->numeric('age');
        
        $this->assertFalse($validator->isValid());
    }

    public function testMultipleRules(): void
    {
        $validator = new Validator(['email' => 'test@example.com', 'password' => 'short']);
        $validator
            ->required('email')
            ->email('email')
            ->required('password')
            ->minLength('password', 8);
        
        $this->assertFalse($validator->isValid());
        $this->assertArrayHasKey('password', $validator->getErrors());
    }
}
