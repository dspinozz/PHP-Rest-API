# PHP REST API Framework

A modern, lightweight, framework-agnostic RESTful API framework for PHP 8.2+.

## Features

- 🚀 **Framework Agnostic** - Works with any PHP application
- 🎯 **PSR Standards** - PSR-7, PSR-11, PSR-15, PSR-17 compliant
- 🔒 **Built-in Security** - JWT authentication, CORS, rate limiting, password hashing, HTTPS enforcement
- 🗄️ **Database Support** - MySQL, PostgreSQL, SQLite via PDO abstraction
- ✅ **Input Validation** - Built-in validator with common rules
- 📝 **Type Safety** - Full PHP 8.2+ type hints and attributes
- 🧪 **Well Tested** - Comprehensive test coverage
- 📚 **Great DX** - Clean API, excellent documentation
- ⚡ **Performance** - Optimized for speed and memory efficiency

## Installation

```bash
composer require your-vendor/php-rest-api-framework
```

## Quick Start

```php
<?php

use RestApi\Application;
use RestApi\Http\RequestHelper;
use RestApi\Validation\Validator;
use RestApi\Exceptions\HttpException;
use RestApi\Exceptions\ErrorCodes;

$app = new Application();

$app->get('/users', function($request) {
    return ['users' => [/* ... */]];
});

$app->post('/users', function($request) {
    $data = RequestHelper::getJsonBody($request);
    
    // Validate input
    $validator = new Validator($data ?? []);
    $validator
        ->required('email')
        ->email('email')
        ->required('name')
        ->minLength('name', 2);
    
    if (!$validator->isValid()) {
        throw new HttpException(
            implode(', ', $validator->getErrorMessages()),
            ErrorCodes::UNPROCESSABLE_ENTITY
        );
    }
    
    // Create user logic
    return ['id' => 123, 'name' => $data['name'], 'email' => $data['email']];
});

$app->run();
```

## Requirements

- PHP 8.2 or higher
- Composer

## License

MIT
