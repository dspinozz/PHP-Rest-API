<?php

declare(strict_types=1);

namespace RestApi\Tests\Integration;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use RestApi\Application;
use RestApi\Database\Database;
use RestApi\Database\Repository\UserRepository;
use RestApi\Middleware\AuthMiddleware;
use RestApi\Security\JwtService;
use RestApi\Security\PasswordHasher;

/**
 * Integration test for complete authentication flow
 * Tests real database operations, JWT generation, and middleware
 */
class AuthIntegrationTest extends TestCase
{
    private Application $app;
    private UserRepository $userRepository;
    private JwtService $jwtService;
    private string $dbPath;
    private string $secretKey;

    protected function setUp(): void
    {
        $this->secretKey = 'test-secret-for-integration-testing';
        $this->dbPath = sys_get_temp_dir() . '/auth_test_' . uniqid() . '.db';
        
        $db = Database::sqlite($this->dbPath);
        $this->userRepository = new UserRepository($db);
        $this->userRepository->initializeTable('sqlite');
        
        $this->jwtService = new JwtService($this->secretKey);
        $this->app = new Application();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testCompleteAuthFlow(): void
    {
        // 1. Register user
        $registerData = json_encode([
            'email' => 'test@example.com',
            'password' => 'SecurePass123'
        ]);
        
        $registerRequest = new ServerRequest(
            'POST',
            '/register',
            ['Content-Type' => 'application/json'],
            Stream::create($registerData)
        );
        
        $userRepo = $this->userRepository;
        $jwtService = $this->jwtService;
        
        $this->app->post('/register', function($request) use ($userRepo, $jwtService) {
            $data = \RestApi\Http\RequestHelper::getJsonBody($request);
            $passwordHash = PasswordHasher::hash($data['password']);
            $user = $userRepo->create($data['email'], $passwordHash);
            $token = $jwtService->generateAccessToken(['sub' => (string)$user->id, 'email' => $user->email]);
            return ['user' => $user->toArray(), 'token' => $token];
        });
        
        $registerResponse = $this->app->handle($registerRequest);
        $this->assertEquals(200, $registerResponse->getStatusCode());
        
        $registerBody = json_decode((string)$registerResponse->getBody(), true);
        $this->assertArrayHasKey('user', $registerBody);
        $this->assertArrayHasKey('token', $registerBody);
        $token = $registerBody['token'];
        
        // 2. Access protected route with token
        $protectedRequest = new ServerRequest('GET', '/me', [
            'Authorization' => 'Bearer ' . $token
        ]);
        
        $authMiddleware = new AuthMiddleware($this->secretKey);
        $this->app->get('/me', function($request) use ($authMiddleware) {
            return $authMiddleware($request, function($req) {
                $user = $req->getAttribute('user');
                if (!is_array($user)) {
                    throw new \RestApi\Exceptions\HttpException('User not found', 401);
                }
                return ['user' => $user];
            });
        });
        
        $protectedResponse = $this->app->handle($protectedRequest);
        $this->assertEquals(200, $protectedResponse->getStatusCode());
        
        $protectedBody = json_decode((string)$protectedResponse->getBody(), true);
        $this->assertArrayHasKey('user', $protectedBody);
        $this->assertEquals('test@example.com', $protectedBody['user']['email']);
    }

    public function testLoginFlow(): void
    {
        // Create user first
        $user = $this->userRepository->create(
            'login@test.com',
            PasswordHasher::hash('Password123')
        );
        
        // Login request
        $loginData = json_encode([
            'email' => 'login@test.com',
            'password' => 'Password123'
        ]);
        
        $loginRequest = new ServerRequest(
            'POST',
            '/login',
            ['Content-Type' => 'application/json'],
            Stream::create($loginData)
        );
        
        $userRepo = $this->userRepository;
        $jwtService = $this->jwtService;
        
        $this->app->post('/login', function($request) use ($userRepo, $jwtService) {
            $data = \RestApi\Http\RequestHelper::getJsonBody($request);
            $user = $userRepo->findByEmail($data['email']);
            
            if (!$user || !PasswordHasher::verify($data['password'], $user->passwordHash)) {
                throw new \RestApi\Exceptions\HttpException('Invalid credentials', 401);
            }
            
            $token = $jwtService->generateAccessToken(['sub' => (string)$user->id, 'email' => $user->email]);
            return ['user' => $user->toArray(), 'token' => $token];
        });
        
        $loginResponse = $this->app->handle($loginRequest);
        $this->assertEquals(200, $loginResponse->getStatusCode());
        
        $loginBody = json_decode((string)$loginResponse->getBody(), true);
        $this->assertArrayHasKey('token', $loginBody);
        $this->assertEquals($user->id, $loginBody['user']['id']);
    }

    public function testLoginWithWrongPassword(): void
    {
        // Create user
        $this->userRepository->create(
            'wrongpass@test.com',
            PasswordHasher::hash('CorrectPassword123')
        );
        
        // Try login with wrong password
        $loginData = json_encode([
            'email' => 'wrongpass@test.com',
            'password' => 'WrongPassword123'
        ]);
        
        $loginRequest = new ServerRequest(
            'POST',
            '/login',
            ['Content-Type' => 'application/json'],
            Stream::create($loginData)
        );
        
        $userRepo = $this->userRepository;
        
        $this->app->post('/login', function($request) use ($userRepo) {
            $data = \RestApi\Http\RequestHelper::getJsonBody($request);
            $user = $userRepo->findByEmail($data['email']);
            
            if (!$user || !PasswordHasher::verify($data['password'], $user->passwordHash)) {
                throw new \RestApi\Exceptions\HttpException('Invalid credentials', 401);
            }
            
            return ['token' => 'should-not-reach-here'];
        });
        
        $loginResponse = $this->app->handle($loginRequest);
        $this->assertEquals(401, $loginResponse->getStatusCode());
    }
}
