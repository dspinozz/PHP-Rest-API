<?php

require_once __DIR__ . '/../vendor/autoload.php';

use RestApi\Application;
use RestApi\Middleware\AuthMiddleware;
use RestApi\Middleware\RateLimitMiddleware;
use RestApi\Security\JwtService;
use RestApi\Security\PasswordHasher;
use RestApi\Database\Database;
use RestApi\Database\Repository\UserRepository;
use RestApi\Http\RequestHelper;
use RestApi\Exceptions\HttpException;
use RestApi\Exceptions\ErrorCodes;
use RestApi\Api\ResponseFormatter;

// Configuration
$jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production';
$dbPath = __DIR__ . '/../data/users.db';

// Initialize services
$jwtService = new JwtService($jwtSecret);
$db = Database::sqlite($dbPath);
$userRepository = new UserRepository($db);

// Initialize user table
try {
    $userRepository->initializeTable('sqlite');
} catch (\Exception $e) {
    // Table might already exist
}

// Create application
$app = new Application();

// Apply rate limiting to all routes
$app->middleware(new RateLimitMiddleware(maxRequests: 100, windowSeconds: 3600));

// Public routes
$app->post('/register', function($request) use ($userRepository, $jwtService) {
    $data = RequestHelper::getJsonBody($request);
    
    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        throw new HttpException('Email and password required', ErrorCodes::BAD_REQUEST);
    }

    // Validate password
    $passwordErrors = PasswordHasher::validateStrength($data['password']);
    if (!empty($passwordErrors)) {
        throw new HttpException(implode(', ', $passwordErrors), ErrorCodes::BAD_REQUEST);
    }

    try {
        // Create user (repository handles duplicate check)
        $passwordHash = PasswordHasher::hash($data['password']);
        $user = $userRepository->create($data['email'], $passwordHash);

        // Generate tokens
        $accessToken = $jwtService->generateAccessToken(['sub' => (string)$user->id, 'email' => $user->email]);
        $refreshToken = $jwtService->generateRefreshToken(['sub' => (string)$user->id]);

        return ResponseFormatter::success([
            'user' => $user->toArray(),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer'
        ], ErrorCodes::CREATED);
    } catch (\RuntimeException $e) {
        if (str_contains($e->getMessage(), 'already exists')) {
            throw new HttpException('User already exists', ErrorCodes::CONFLICT);
        }
        throw $e;
    }
});

$app->post('/login', function($request) use ($userRepository, $jwtService) {
    $data = RequestHelper::getJsonBody($request);
    
    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        throw new HttpException('Email and password required', ErrorCodes::BAD_REQUEST);
    }

    // Find user
    $user = $userRepository->findByEmail($data['email']);
    if (!$user) {
        throw new HttpException('Invalid credentials', ErrorCodes::UNAUTHORIZED);
    }

    // Verify password
    if (!PasswordHasher::verify($data['password'], $user->passwordHash)) {
        throw new HttpException('Invalid credentials', ErrorCodes::UNAUTHORIZED);
    }

    // Generate tokens
    $accessToken = $jwtService->generateAccessToken(['sub' => (string)$user->id, 'email' => $user->email]);
    $refreshToken = $jwtService->generateRefreshToken(['sub' => (string)$user->id]);

    return ResponseFormatter::success([
        'user' => $user->toArray(),
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'token_type' => 'Bearer'
    ]);
});

// Protected routes (require authentication)
// Note: For route-specific middleware, apply it before the route handler
$authMiddleware = new AuthMiddleware($jwtSecret);

$app->get('/me', function($request) use ($authMiddleware) {
    // Apply auth middleware
    $response = $authMiddleware($request, function($req) {
        $user = $req->getAttribute('user');
        if (!is_array($user)) {
            throw new HttpException('User data not found', 401);
        }
        return ['user' => $user];
    });
    return $response;
});

$app->get('/users', function($request) use ($userRepository, $authMiddleware) {
    // Apply auth middleware
    $response = $authMiddleware($request, function($req) use ($userRepository) {
        $users = $userRepository->findAll();
        $userArrays = array_map(fn($user) => $user->toArray(), $users);
        return ResponseFormatter::success(['users' => $userArrays]);
    });
    return $response;
});

// Run application
$app->run();
