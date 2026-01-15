<?php

declare(strict_types=1);

// Load autoloader
$autoloader = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    die("Error: vendor/autoload.php not found. Run 'composer install' first.\n");
}
require_once $autoloader;

use RestApi\Application\Application;
use RestApi\Http\RequestHelper;
use RestApi\Validation\Validator;
use RestApi\Exceptions\HttpException;
use RestApi\Exceptions\ErrorCodes;
use RestApi\Security\PasswordHasher;
use RestApi\Security\JwtService;

$app = new \RestApi\Application\Application();

// Simple health check
$app->get('/', function($request) {
    return [
        'status' => 'ok',
        'message' => 'PHP REST API Framework is running',
        'version' => '1.0.0'
    ];
});

// Echo endpoint
$app->get('/echo/{message}', function($request, $params) {
    return [
        'echo' => $params['message'],
        'timestamp' => date('c')
    ];
});

// JSON POST test
$app->post('/test', function($request) {
    $data = RequestHelper::getJsonBody($request);
    
    if (!$data) {
        throw new HttpException('Invalid JSON', ErrorCodes::BAD_REQUEST);
    }
    
    return [
        'received' => $data,
        'processed' => true,
        'timestamp' => date('c')
    ];
});

// Validation test
$app->post('/validate', function($request) {
    $data = RequestHelper::getJsonBody($request) ?? [];
    
    $validator = new Validator($data);
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
    
    return [
        'valid' => true,
        'data' => $data
    ];
});

// Protected route (requires JWT)
$secretKey = $_ENV['JWT_SECRET'] ?? 'test-secret-key-for-curl-testing';
$jwtService = new JwtService($secretKey);

$app->get('/protected', function($request) use ($jwtService) {
    $authHeader = $request->getHeaderLine('Authorization');
    
    if (!str_starts_with($authHeader, 'Bearer ')) {
        throw new HttpException('Missing or invalid token', ErrorCodes::UNAUTHORIZED);
    }
    
    $token = substr($authHeader, 7);
    $payload = $jwtService->validateToken($token);
    
    return [
        'message' => 'Protected route accessed',
        'user' => $payload,
        'timestamp' => date('c')
    ];
});

// Login endpoint (returns JWT)
$app->post('/login', function($request) use ($jwtService) {
    $data = RequestHelper::getJsonBody($request) ?? [];
    
    // Simple mock login (in real app, check database)
    if (($data['email'] ?? '') === 'test@example.com' && 
        ($data['password'] ?? '') === 'password123') {
        
        $token = $jwtService->generateAccessToken([
            'sub' => '123',
            'email' => 'test@example.com'
        ]);
        
        return [
            'token' => $token,
            'user' => [
                'id' => '123',
                'email' => 'test@example.com'
            ]
        ];
    }
    
    throw new HttpException('Invalid credentials', ErrorCodes::UNAUTHORIZED);
});

// Run the application
$app->run();
