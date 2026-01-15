# API Documentation

## Endpoints Overview

### Authentication

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/register` | Create new user account | No |
| POST | `/login` | Authenticate and get tokens | No |

### Users

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/me` | Get current user profile | Yes |
| GET | `/users` | List all users | Yes |
| GET | `/users/{id}` | Get user by ID | Yes |
| PUT | `/users/{id}` | Update user (full) | Yes (own profile) |
| PATCH | `/users/{id}` | Update user (partial) | Yes (own profile) |
| DELETE | `/users/{id}` | Delete user account | Yes (own account) |

### System

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/health` | Health check | No |

---

## Authentication

### Register

```http
POST /register
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "SecurePassword123!"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "email": "user@example.com",
            "created_at": "2024-01-15 10:30:00"
        },
        "access_token": "eyJ...",
        "refresh_token": "eyJ...",
        "token_type": "Bearer"
    }
}
```

### Login

```http
POST /login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "SecurePassword123!"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "email": "user@example.com",
            "created_at": "2024-01-15 10:30:00"
        },
        "access_token": "eyJ...",
        "refresh_token": "eyJ...",
        "token_type": "Bearer"
    }
}
```

---

## User Operations

### Get Current User

```http
GET /me
Authorization: Bearer <access_token>
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "user": {
            "sub": "1",
            "email": "user@example.com"
        }
    }
}
```

### List All Users

```http
GET /users
Authorization: Bearer <access_token>
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "users": [
            {
                "id": 1,
                "email": "user@example.com",
                "created_at": "2024-01-15 10:30:00"
            }
        ],
        "total": 1
    }
}
```

### Get User by ID

```http
GET /users/1
Authorization: Bearer <access_token>
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "email": "user@example.com",
            "created_at": "2024-01-15 10:30:00"
        }
    }
}
```

### Update User

```http
PUT /users/1
Authorization: Bearer <access_token>
Content-Type: application/json

{
    "email": "newemail@example.com",
    "password": "NewSecurePassword123!"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "message": "User updated successfully",
        "user": {
            "id": 1,
            "email": "newemail@example.com",
            "created_at": "2024-01-15 10:30:00"
        }
    }
}
```

**Note:** Users can only update their own profile. Updating another user's profile returns 403 Forbidden.

### Partial Update User

```http
PATCH /users/1
Authorization: Bearer <access_token>
Content-Type: application/json

{
    "email": "newemail@example.com"
}
```

Works the same as PUT but only specified fields are required.

### Delete User

```http
DELETE /users/1
Authorization: Bearer <access_token>
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "message": "User deleted successfully"
    }
}
```

**Note:** Users can only delete their own account.

---

## Error Responses

### 400 Bad Request
```json
{
    "success": false,
    "error": "Email and password required"
}
```

### 401 Unauthorized
```json
{
    "success": false,
    "error": "Invalid credentials"
}
```

### 403 Forbidden
```json
{
    "success": false,
    "error": "You can only update your own profile"
}
```

### 404 Not Found
```json
{
    "success": false,
    "error": "User not found"
}
```

### 409 Conflict
```json
{
    "success": false,
    "error": "User already exists"
}
```

---

## Framework Reference

### Application

```php
$app = new Application();
$app->get('/path', $handler);
$app->post('/path', $handler);
$app->put('/path', $handler);
$app->delete('/path', $handler);
$app->patch('/path', $handler);
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

$data = RequestHelper::getJsonBody($request);
$page = RequestHelper::getQueryParam($request, 'page', 1);
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

### Database

```php
use RestApi\Database\Database;

$db = Database::sqlite('./data.db');
$db = Database::mysql('localhost', 'mydb', 'user', 'pass');
$db = Database::postgresql('localhost', 'mydb', 'user', 'pass');

$users = $db->query('SELECT * FROM users WHERE id = ?', [$id]);
$user = $db->queryOne('SELECT * FROM users WHERE id = ?', [$id]);
$db->execute('INSERT INTO users (name) VALUES (?)', [$name]);
```

### Middleware

```php
use RestApi\Middleware\RateLimitMiddleware;
use RestApi\Middleware\CorsMiddleware;
use RestApi\Middleware\AuthMiddleware;

$app->middleware(new RateLimitMiddleware(100, 3600));
$app->middleware(new CorsMiddleware());
```

## Examples

See `examples/` directory for complete examples:
- `auth-example.php` - Full CRUD API with authentication
- `basic.php` - Basic routing
- `validation-example.php` - Input validation
