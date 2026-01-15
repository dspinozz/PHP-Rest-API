# Architecture Overview

## Core Components

### 1. Application (`Application.php`)
- Main entry point
- Manages request/response lifecycle
- Handles middleware pipeline
- Dispatches routes

### 2. Router (`Router/`)
- Route registration and matching
- HTTP method support (GET, POST, PUT, DELETE, PATCH, OPTIONS)
- Route parameters and constraints
- Route groups and prefixes

### 3. Request/Response (`Http/`)
- PSR-7 Request/Response wrappers
- Request validation
- Response formatting (JSON, XML)
- Status code helpers

### 4. Middleware (`Middleware/`)
- Authentication (JWT)
- CORS handling
- Rate limiting
- Request logging
- Error handling

### 5. Container (`Container/`)
- Dependency injection
- Service registration
- PSR-11 compliant

### 6. Validation (`Validation/`)
- Request data validation
- Rule-based validation
- Custom validators

## Design Principles

1. **PSR Compliance** - Follows PSR standards for interoperability
2. **Framework Agnostic** - No dependencies on Laravel, Symfony, etc.
3. **Type Safety** - Full PHP 8.2+ type hints
4. **Composability** - Components work independently
5. **Performance** - Optimized for speed

## Request Flow

```
HTTP Request
    ↓
PSR-7 Request
    ↓
Middleware Pipeline
    ↓
Router Matching
    ↓
Controller/Handler
    ↓
Response
    ↓
PSR-7 Response
    ↓
HTTP Response
```
