# PHP REST API Framework

A modern, lightweight, framework-agnostic RESTful API framework for PHP 8.2+. Built with PSR standards, comprehensive security features, and production-ready out of the box.

## 🚀 Features

- **Framework Agnostic** - Works with any PHP application, no framework lock-in
- **PSR Standards** - Fully compliant with PSR-7, PSR-15, and PSR-17
- **Built-in Security** - JWT authentication, CORS, rate limiting, password hashing, HTTPS enforcement
- **Database Support** - MySQL, PostgreSQL, and SQLite via simple PDO abstraction
- **Input Validation** - Built-in validator with common rules (email, required, minLength, etc.)
- **Type Safety** - Full PHP 8.2+ type hints and strict types throughout
- **Well Tested** - 95+ genuine tests (no mocks) with comprehensive coverage
- **Great DX** - Clean API, excellent documentation, easy to use
- **Performance** - Optimized for speed and memory efficiency

## 📋 Requirements

- PHP 8.2 or higher
- Composer (for dependency management)
- PDO extension (for database support)
- OpenSSL extension (for JWT tokens)

## 📦 Installation

### Step 1: Install via Composer

```bash
composer require your-vendor/php-rest-api-framework
```

Or if you're cloning this repository:

```bash
git clone https://github.com/dspinozz/PHP-Rest-API.git
cd PHP-Rest-API
composer install
```

### Step 2: Set Up Environment Variables

Create a `.env` file in your project root:

```bash
cp .env.example .env
```

Edit `.env` and set your configuration:

```env
JWT_SECRET=your-secret-key-here-make-it-long-and-random
DB_DRIVER=sqlite
DB_PATH=/path/to/database.db
```

For MySQL or PostgreSQL:

```env
JWT_SECRET=your-secret-key-here
DB_DRIVER=mysql
DB_HOST=localhost
DB_NAME=my_database
DB_USER=my_user
DB_PASS=my_password
```

### Step 3: Include the Autoloader

In your PHP file, include the Composer autoloader:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
```

## 🎯 Quick Start

### Basic Example

Here's a simple "Hello World" API:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use RestApi\Application\Application;

$app = new Application();

// Simple GET endpoint
$app->get('/', function($request) {
    return [
        'message' => 'Hello, World!',
        'status' => 'ok'
    ];
});

// GET endpoint with path parameter
$app->get('/users/{id}', function($request, $params) {
    return [
        'user_id' => $params['id'],
        'name' => 'John Doe'
    ];
});

// POST endpoint with JSON body
$app->post('/users', function($request) {
    $data = \RestApi\Http\RequestHelper::getJsonBody($request);
    
    return [
        'created' => true,
        'data' => $data
    ];
});

// Run the application
$app->run();
```

### Step-by-Step: Creating Your First API

#### Step 1: Create Your Entry Point

Create a file called `index.php`:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use RestApi\Application\Application;

$app = new Application();
```

#### Step 2: Define Your Routes

Add routes to your application:

```php
// Health check endpoint
$app->get('/', function($request) {
    return ['status' => 'ok'];
});

// Get user by ID
$app->get('/users/{id}', function($request, $params) {
    $userId = $params['id'];
    // Your logic here
    return ['user_id' => $userId];
});
```

#### Step 3: Add Request Handling

Handle POST requests with validation:

```php
use RestApi\Http\RequestHelper;
use RestApi\Validation\Validator;
use RestApi\Exceptions\HttpException;
use RestApi\Exceptions\ErrorCodes;

$app->post('/users', function($request) {
    // Get JSON body
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
    
    // Create user logic here
    return ['id' => 123, 'name' => $data['name'], 'email' => $data['email']];
});
```

#### Step 4: Run Your Application

At the end of your file:

```php
$app->run();
```

#### Step 5: Start the Server

Using PHP's built-in server:

```bash
php -S localhost:8000 index.php
```

Or with Docker:

```bash
docker run -p 8000:8000 -v $(pwd):/app -w /app php:8.2-cli php -S 0.0.0.0:8000 index.php
```

#### Step 6: Test Your API

```bash
# Health check
curl http://localhost:8000/

# Get user
curl http://localhost:8000/users/123

# Create user
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com"}'
```

## 🔐 Authentication Example

### Step 1: Set Up JWT Service

```php
use RestApi\Security\JwtService;

$secretKey = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
$jwtService = new JwtService($secretKey);
```

### Step 2: Create Login Endpoint

```php
use RestApi\Http\RequestHelper;
use RestApi\Security\PasswordHasher;

$app->post('/login', function($request) use ($jwtService) {
    $data = RequestHelper::getJsonBody($request);
    
    // Validate credentials (check against your database)
    if ($data['email'] === 'user@example.com' && 
        PasswordHasher::verify($data['password'], $storedHash)) {
        
        // Generate JWT token
        $token = $jwtService->generateAccessToken([
            'sub' => 'user123',
            'email' => $data['email']
        ]);
        
        return ['token' => $token];
    }
    
    throw new HttpException('Invalid credentials', 401);
});
```

### Step 3: Protect Routes with Middleware

```php
use RestApi\Middleware\AuthMiddleware;

$authMiddleware = new AuthMiddleware($secretKey);

$app->get('/protected', function($request) {
    // This route requires authentication
    $user = $request->getAttribute('user');
    return ['message' => 'Protected data', 'user' => $user];
}, [$authMiddleware]);
```

## 🗄️ Database Example

### Step 1: Set Up Database Connection

```php
use RestApi\Database\Database;

// SQLite
$db = Database::sqlite('/path/to/database.db');

// MySQL
$db = Database::mysql('localhost', 'database', 'user', 'password');

// PostgreSQL
$db = Database::postgresql('localhost', 'database', 'user', 'password');
```

### Step 2: Create Tables

```php
$db->execute("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
```

### Step 3: Use Repository Pattern

```php
use RestApi\Database\Repository\UserRepository;

$repository = new UserRepository($db);
$repository->initializeTable('sqlite');

// Create user
$user = $repository->create('user@example.com', $passwordHash);

// Find user
$user = $repository->findByEmail('user@example.com');
```

## 🛡️ Security Features

### Password Hashing

```php
use RestApi\Security\PasswordHasher;

// Hash password
$hash = PasswordHasher::hash('user-password');

// Verify password
if (PasswordHasher::verify('user-password', $hash)) {
    // Password is correct
}

// Validate password strength
$errors = PasswordHasher::validateStrength('password123');
if (empty($errors)) {
    // Password meets requirements
}
```

### Input Validation

```php
use RestApi\Validation\Validator;

$validator = new Validator($data);
$validator
    ->required('email')
    ->email('email')
    ->required('name')
    ->minLength('name', 2)
    ->numeric('age');

if (!$validator->isValid()) {
    $errors = $validator->getErrorMessages();
    // Handle validation errors
}
```

### CORS Middleware

```php
use RestApi\Middleware\CorsMiddleware;

$cors = new CorsMiddleware(['https://example.com']);
$app->use($cors);
```

### Rate Limiting

```php
use RestApi\Middleware\RateLimitMiddleware;

// 100 requests per 60 seconds
$rateLimit = new RateLimitMiddleware(100, 60);
$app->use($rateLimit);
```

## 📚 Available HTTP Methods

The framework supports all standard HTTP methods:

```php
$app->get('/path', $handler);
$app->post('/path', $handler);
$app->put('/path', $handler);
$app->delete('/path', $handler);
$app->patch('/path', $handler);
$app->options('/path', $handler);
```

## 🧪 Testing

### Running Tests

```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit tests/Unit/Security/JwtServiceTest.php
```

### Test Coverage

The framework includes:
- **19 test files** with 95+ test methods
- **Unit tests** for all components
- **Integration tests** for complete flows
- **All genuine tests** - no mocks, real implementations

See `TEST_VERIFICATION.md` for details.

## 📖 Documentation

- **[API Documentation](API_DOCUMENTATION.md)** - Complete API reference
- **[Architecture](ARCHITECTURE.md)** - Design decisions and patterns
- **[Security Features](SECURITY_FEATURES.md)** - Security implementation details
- **[Authentication](AUTHENTICATION_SUMMARY.md)** - Authentication guide

## 🔧 Configuration

### Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `JWT_SECRET` | Secret key for JWT tokens | Yes |
| `DB_DRIVER` | Database driver (mysql, pgsql, sqlite) | Yes |
| `DB_HOST` | Database host (MySQL/PostgreSQL) | Conditional |
| `DB_NAME` | Database name (MySQL/PostgreSQL) | Conditional |
| `DB_USER` | Database user (MySQL/PostgreSQL) | Conditional |
| `DB_PASS` | Database password (MySQL/PostgreSQL) | Conditional |
| `DB_PATH` | Database file path (SQLite) | Conditional |

## 🚦 Common Use Cases

### Building a REST API

1. **Define your routes** - Use `$app->get()`, `$app->post()`, etc.
2. **Handle requests** - Use `RequestHelper` to get data
3. **Validate input** - Use `Validator` for validation
4. **Access database** - Use `Database` or `Repository` classes
5. **Return responses** - Return arrays (automatically converted to JSON)

### Adding Authentication

1. **Create login endpoint** - Generate JWT tokens
2. **Add AuthMiddleware** - Protect routes
3. **Access user data** - Use `$request->getAttribute('user')`

### Error Handling

```php
use RestApi\Exceptions\HttpException;
use RestApi\Exceptions\ErrorCodes;

// Throw HTTP exceptions
throw new HttpException('User not found', ErrorCodes::NOT_FOUND);
throw new HttpException('Invalid input', ErrorCodes::BAD_REQUEST);
throw new HttpException('Unauthorized', ErrorCodes::UNAUTHORIZED);
```

## 🐳 Docker Support

### Using Docker

```bash
# Build and run
docker-compose -f docker-compose.test.yml up

# Or manually
docker run -p 8000:8000 -v $(pwd):/app -w /app php:8.2-cli \
  php -S 0.0.0.0:8000 index.php
```

## 📝 Examples

Check the `examples/` directory for complete working examples:

- `basic.php` - Basic routing and request handling
- `auth-example.php` - Complete authentication flow
- `validation-example.php` - Input validation examples

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built with PSR standards (PSR-7, PSR-15, PSR-17)
- Uses [Firebase JWT](https://github.com/firebase/php-jwt) for token handling
- Uses [GuzzleHttp PSR-7](https://github.com/guzzle/psr7) for HTTP messages

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/dspinozz/PHP-Rest-API/issues)
- **Documentation**: See the `docs/` directory
- **Examples**: See the `examples/` directory

---

**Made with ❤️ for the PHP community**
