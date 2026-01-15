<?php

require_once __DIR__ . '/../vendor/autoload.php';

use RestApi\Application\Application;
use RestApi\Middleware\AuthMiddleware;
use RestApi\Middleware\RateLimitMiddleware;
use RestApi\Security\JwtService;
use RestApi\Security\PasswordHasher;
use RestApi\Database\Database;
use RestApi\Database\Repository\UserRepository;
use RestApi\Http\RequestHelper;
use RestApi\Http\ResponseFactory;
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
$responseFactory = new ResponseFactory();

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

// Auth middleware instance
$authMiddleware = new AuthMiddleware($jwtSecret);

// =============================================================================
// PUBLIC ROUTES
// =============================================================================

/**
 * POST /register - Create a new user
 */
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

/**
 * POST /login - Authenticate user
 */
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

// =============================================================================
// PROTECTED ROUTES (require authentication)
// =============================================================================

/**
 * GET /me - Get current authenticated user
 */
$app->get('/me', function($request) use ($authMiddleware, $responseFactory) {
    return $authMiddleware($request, function($req) use ($responseFactory) {
        $user = $req->getAttribute('user');
        if (!is_array($user)) {
            throw new HttpException('User data not found', ErrorCodes::UNAUTHORIZED);
        }
        return $responseFactory->json(ResponseFormatter::success(['user' => $user]));
    });
});

/**
 * GET /users - List all users
 */
$app->get('/users', function($request) use ($userRepository, $authMiddleware, $responseFactory) {
    return $authMiddleware($request, function($req) use ($userRepository, $responseFactory) {
        $users = $userRepository->findAll();
        $userArrays = array_map(fn($user) => $user->toArray(), $users);
        return $responseFactory->json(ResponseFormatter::success([
            'users' => $userArrays,
            'total' => count($userArrays)
        ]));
    });
});

/**
 * GET /users/{id} - Get user by ID
 */
$app->get('/users/{id}', function($request, $params) use ($userRepository, $authMiddleware, $responseFactory) {
    return $authMiddleware($request, function($req) use ($userRepository, $params, $responseFactory) {
        $id = (int)$params['id'];
        
        if ($id <= 0) {
            throw new HttpException('Invalid user ID', ErrorCodes::BAD_REQUEST);
        }

        $user = $userRepository->findById($id);
        if (!$user) {
            throw new HttpException('User not found', ErrorCodes::NOT_FOUND);
        }

        return $responseFactory->json(ResponseFormatter::success(['user' => $user->toArray()]));
    });
});

/**
 * PUT /users/{id} - Update user
 */
$app->put('/users/{id}', function($request, $params) use ($userRepository, $authMiddleware, $responseFactory) {
    return $authMiddleware($request, function($req) use ($userRepository, $params, $responseFactory) {
        $id = (int)$params['id'];
        $currentUser = $req->getAttribute('user');
        
        if ($id <= 0) {
            throw new HttpException('Invalid user ID', ErrorCodes::BAD_REQUEST);
        }

        // Users can only update their own profile
        if (!is_array($currentUser) || (int)$currentUser['sub'] !== $id) {
            throw new HttpException('You can only update your own profile', ErrorCodes::FORBIDDEN);
        }

        $data = RequestHelper::getJsonBody($req);
        if (!$data) {
            throw new HttpException('Request body required', ErrorCodes::BAD_REQUEST);
        }

        $updateData = [];

        // Handle email update
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new HttpException('Invalid email format', ErrorCodes::BAD_REQUEST);
            }
            $updateData['email'] = $data['email'];
        }

        // Handle password update
        if (isset($data['password'])) {
            $passwordErrors = PasswordHasher::validateStrength($data['password']);
            if (!empty($passwordErrors)) {
                throw new HttpException(implode(', ', $passwordErrors), ErrorCodes::BAD_REQUEST);
            }
            $updateData['password_hash'] = PasswordHasher::hash($data['password']);
        }

        if (empty($updateData)) {
            throw new HttpException('No valid fields to update (email, password)', ErrorCodes::BAD_REQUEST);
        }

        try {
            $user = $userRepository->update($id, $updateData);
            return $responseFactory->json(ResponseFormatter::success([
                'message' => 'User updated successfully',
                'user' => $user->toArray()
            ]));
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                throw new HttpException('User not found', ErrorCodes::NOT_FOUND);
            }
            if (str_contains($e->getMessage(), 'already in use')) {
                throw new HttpException('Email already in use', ErrorCodes::CONFLICT);
            }
            throw $e;
        }
    });
});

/**
 * PATCH /users/{id} - Partial update user
 */
$app->patch('/users/{id}', function($request, $params) use ($userRepository, $authMiddleware, $responseFactory) {
    return $authMiddleware($request, function($req) use ($userRepository, $params, $responseFactory) {
        $id = (int)$params['id'];
        $currentUser = $req->getAttribute('user');
        
        if ($id <= 0) {
            throw new HttpException('Invalid user ID', ErrorCodes::BAD_REQUEST);
        }

        // Users can only update their own profile
        if (!is_array($currentUser) || (int)$currentUser['sub'] !== $id) {
            throw new HttpException('You can only update your own profile', ErrorCodes::FORBIDDEN);
        }

        $data = RequestHelper::getJsonBody($req);
        if (!$data) {
            throw new HttpException('Request body required', ErrorCodes::BAD_REQUEST);
        }

        $updateData = [];

        // Handle email update
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new HttpException('Invalid email format', ErrorCodes::BAD_REQUEST);
            }
            $updateData['email'] = $data['email'];
        }

        // Handle password update
        if (isset($data['password'])) {
            $passwordErrors = PasswordHasher::validateStrength($data['password']);
            if (!empty($passwordErrors)) {
                throw new HttpException(implode(', ', $passwordErrors), ErrorCodes::BAD_REQUEST);
            }
            $updateData['password_hash'] = PasswordHasher::hash($data['password']);
        }

        if (empty($updateData)) {
            throw new HttpException('No valid fields to update (email, password)', ErrorCodes::BAD_REQUEST);
        }

        try {
            $user = $userRepository->update($id, $updateData);
            return $responseFactory->json(ResponseFormatter::success([
                'message' => 'User updated successfully',
                'user' => $user->toArray()
            ]));
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                throw new HttpException('User not found', ErrorCodes::NOT_FOUND);
            }
            if (str_contains($e->getMessage(), 'already in use')) {
                throw new HttpException('Email already in use', ErrorCodes::CONFLICT);
            }
            throw $e;
        }
    });
});

/**
 * DELETE /users/{id} - Delete user
 */
$app->delete('/users/{id}', function($request, $params) use ($userRepository, $authMiddleware, $responseFactory) {
    return $authMiddleware($request, function($req) use ($userRepository, $params, $responseFactory) {
        $id = (int)$params['id'];
        $currentUser = $req->getAttribute('user');
        
        if ($id <= 0) {
            throw new HttpException('Invalid user ID', ErrorCodes::BAD_REQUEST);
        }

        // Users can only delete their own account
        if (!is_array($currentUser) || (int)$currentUser['sub'] !== $id) {
            throw new HttpException('You can only delete your own account', ErrorCodes::FORBIDDEN);
        }

        try {
            $userRepository->delete($id);
            return $responseFactory->json(ResponseFormatter::success([
                'message' => 'User deleted successfully'
            ]));
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                throw new HttpException('User not found', ErrorCodes::NOT_FOUND);
            }
            throw $e;
        }
    });
});

// =============================================================================
// HEALTH CHECK
// =============================================================================

/**
 * GET /health - Health check endpoint
 */
$app->get('/health', function($request) use ($userRepository) {
    try {
        $count = $userRepository->count();
        return ResponseFormatter::success([
            'status' => 'healthy',
            'database' => 'connected',
            'users_count' => $count
        ]);
    } catch (\Exception $e) {
        return ResponseFormatter::error('Database connection failed', ErrorCodes::INTERNAL_SERVER_ERROR);
    }
});

// Run application
$app->run();
