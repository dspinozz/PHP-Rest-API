# API Documentation

## Quick Reference

### Application

```php
$app = new Application();
$app->get('/path', $handler);
$app->post('/path', $handler);
$app->put('/path', $handler);
$app->delete('/path', $handler);
$app->patch('/path', $handler);
$app->options('/path', $handler);
$app->middleware($middleware);
$app->run();
```

### Route Handlers

```php
// Simple handler
$app->get('/users', function($request) {
    return ['users' => []];
});

// Handler with parameters
$app->get('/users/{id}', function($request, $params) {
    $id = $params['id'];
    return ['id' => $id];
});
```

### Request Helpers

```php
use RestApi\Http\RequestHelper;

// Get JSON body
$data = RequestHelper::getJsonBody($request);

// Get query parameter
$page = RequestHelper::getQueryParam($request, 'page', 1);

// Get all query params
$params = RequestHelper::getQueryParams($request);
```

### Validation

```php
use RestApi\Validation\Validator;

$validator = new Validator($data);
$validator
    ->required('email')
    ->email('email')
    ->required('password')
    ->minLength('password', 8);

if (!$validator->isValid()) {
    throw new HttpException(
        implode(', ', $validator->getErrorMessages()),
        ErrorCodes::UNPROCESSABLE_ENTITY
    );
}
```

### Authentication

```php
use RestApi\Middleware\AuthMiddleware;
use RestApi\Security\JwtService;

$jwtService = new JwtService('your-secret-key');
$authMiddleware = new AuthMiddleware('your-secret-key');

// Apply to route
$app->get('/protected', function($request) use ($authMiddleware) {
    return $authMiddleware($request, function($req) {
        $user = $req->getAttribute('user');
        return ['user' => $user];
    });
});
```

### Database

```php
use RestApi\Database\Database;

// SQLite
$db = Database::sqlite('./data.db');

// MySQL
$db = Database::mysql('localhost', 'mydb', 'user', 'pass');

// PostgreSQL
$db = Database::postgresql('localhost', 'mydb', 'user', 'pass');

// Query
$users = $db->query('SELECT * FROM users WHERE id = ?', [$id]);
$user = $db->queryOne('SELECT * FROM users WHERE id = ?', [$id]);
$db->execute('INSERT INTO users (name) VALUES (?)', [$name]);
```

### Middleware

```php
use RestApi\Middleware\RateLimitMiddleware;
use RestApi\Middleware\CorsMiddleware;
use RestApi\Middleware\HttpsMiddleware;

// Rate limiting
$app->middleware(new RateLimitMiddleware(100, 3600));

// CORS
$app->middleware(new CorsMiddleware());

// HTTPS enforcement
$app->middleware(new HttpsMiddleware());
```

## Error Handling

```php
use RestApi\Exceptions\HttpException;
use RestApi\Exceptions\ErrorCodes;

// Throw HTTP exception
throw new HttpException('User not found', ErrorCodes::NOT_FOUND);

// Available error codes
ErrorCodes::BAD_REQUEST (400)
ErrorCodes::UNAUTHORIZED (401)
ErrorCodes::NOT_FOUND (404)
ErrorCodes::UNPROCESSABLE_ENTITY (422)
ErrorCodes::INTERNAL_SERVER_ERROR (500)
```

## Examples

See `examples/` directory for complete examples:
- `basic.php` - Basic routing
- `auth-example.php` - Authentication flow
- `validation-example.php` - Input validation
