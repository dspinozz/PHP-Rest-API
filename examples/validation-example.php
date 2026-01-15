<?php

require_once __DIR__ . '/../vendor/autoload.php';

use RestApi\Application;
use RestApi\Http\RequestHelper;
use RestApi\Validation\Validator;
use RestApi\Exceptions\HttpException;
use RestApi\Exceptions\ErrorCodes;

$app = new Application();

// Example: User registration with validation
$app->post('/users', function($request) {
    $data = RequestHelper::getJsonBody($request);
    
    if ($data === null) {
        throw new HttpException('Invalid JSON body', ErrorCodes::BAD_REQUEST);
    }

    // Validate input
    $validator = new Validator($data);
    $validator
        ->required('email')
        ->email('email')
        ->required('password')
        ->minLength('password', 8)
        ->maxLength('password', 100);

    if (!$validator->isValid()) {
        throw new HttpException(
            'Validation failed: ' . implode(', ', $validator->getErrorMessages()),
            ErrorCodes::UNPROCESSABLE_ENTITY
        );
    }

    // Process valid data
    return [
        'message' => 'User created successfully',
        'email' => $data['email']
    ];
});

// Example: Query parameter validation
$app->get('/users/{id}', function($request, $params) {
    $id = $params['id'] ?? null;
    
    if ($id === null) {
        throw new HttpException('User ID required', ErrorCodes::BAD_REQUEST);
    }

    // Validate ID is numeric
    $validator = new Validator(['id' => $id]);
    $validator->integer('id');
    
    if (!$validator->isValid()) {
        throw new HttpException('Invalid user ID', ErrorCodes::BAD_REQUEST);
    }

    return [
        'id' => (int)$id,
        'name' => 'John Doe'
    ];
});

$app->run();
