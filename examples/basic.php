<?php

require_once __DIR__ . '/../vendor/autoload.php';

use RestApi\Application;
use RestApi\Http\RequestHelper;

// Create application instance
$app = new Application();

// Define routes
$app->get('/', function($request) {
    return [
        'message' => 'Welcome to PHP REST API Framework',
        'version' => '1.0.0'
    ];
});

$app->get('/users', function($request) {
    return [
        'users' => [
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Smith'],
        ]
    ];
});

$app->get('/users/{id}', function($request, $params) {
    $id = (int)($params['id'] ?? 0);
    
    if ($id <= 0) {
        throw new \RestApi\Exceptions\HttpException('Invalid user ID', 400);
    }
    
    return [
        'id' => $id,
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ];
});

$app->post('/users', function($request) {
    // Parse JSON body
    $data = RequestHelper::getJsonBody($request);
    
    if ($data === null) {
        throw new \RestApi\Exceptions\HttpException('Invalid JSON body', 400);
    }
    
    // In a real app, you'd validate and save the user
    return [
        'id' => 123,
        'name' => $data['name'] ?? 'New User',
        'created' => true
    ];
});

// Run the application
$app->run();
