# API Response Standards

This document outlines the standardized API response format implemented across the learning center application, following consistent patterns and best practices for API development.

## Overview

All API endpoints in the learning center application follow a consistent response format that includes:
- **Standardized Structure**: Consistent JSON structure across all endpoints
- **HTTP Status Codes**: Proper HTTP status codes for different scenarios
- **Error Handling**: Comprehensive error response format
- **Pagination Support**: Built-in pagination for list endpoints
- **Documentation**: Comprehensive API documentation using Scribe <mcreference link="https://scribe.knuckles.wtf/laravel/documenting" index="0">0</mcreference>

## Standardized Response Format

### Base Response Structure

All API responses follow this standardized format:

```json
{
  "code": 200,
  "message": "Success",
  "data": [],
  "pagination": {}
}
```

#### Response Fields

- **`code`** (int): HTTP status code
- **`message`** (string): Human-readable response message
- **`data`** (mixed): Response data (object, array, or empty array)
- **`pagination`** (object): Pagination information (empty object if not applicable)

## ApiResponse Trait

### Implementation

**File**: `app/Traits/ApiResponse.php`

The `ApiResponse` trait provides standardized methods for generating consistent API responses:

```php
use App\Traits\ApiResponse;

class YourController extends Controller
{
    use ApiResponse;
    
    public function index()
    {
        return $this->successResponse($data, 'Success message');
    }
}
```

### Available Methods

#### 1. Success Response
```php
protected function successResponse($data = [], string $message = 'Success', int $code = 200, array $pagination = []): JsonResponse
```

**Usage:**
```php
return $this->successResponse($user, 'User retrieved successfully');
```

**Output:**
```json
{
  "code": 200,
  "message": "User retrieved successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "pagination": {}
}
```

#### 2. Error Response
```php
protected function errorResponse(string $message = 'Error', int $code = 400, $data = []): JsonResponse
```

**Usage:**
```php
return $this->errorResponse('User not found', 404);
```

**Output:**
```json
{
  "code": 404,
  "message": "User not found",
  "data": [],
  "pagination": {}
}
```

#### 3. Validation Error Response
```php
protected function validationErrorResponse(array $errors, string $message = 'Validation failed', int $code = 422): JsonResponse
```

**Usage:**
```php
if ($validator->fails()) {
    return $this->validationErrorResponse($validator->errors()->toArray());
}
```

**Output:**
```json
{
  "code": 422,
  "message": "Validation failed",
  "data": {
    "errors": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  },
  "pagination": {}
}
```

#### 4. Paginated Response
```php
protected function paginatedResponse($data, array $pagination, string $message = 'Success', int $code = 200): JsonResponse
```

**Usage:**
```php
$users = User::paginate(15);
$pagination = [
    'current_page' => $users->currentPage(),
    'per_page' => $users->perPage(),
    'total' => $users->total(),
    'last_page' => $users->lastPage(),
    'from' => $users->firstItem(),
    'to' => $users->lastItem(),
];

return $this->paginatedResponse($users->items(), $pagination, 'Users retrieved successfully');
```

**Output:**
```json
{
  "code": 200,
  "message": "Users retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4,
    "from": 1,
    "to": 15
  }
}
```

## HTTP Status Codes

### Success Responses
- **200 OK**: Successful GET, PUT, PATCH requests
- **201 Created**: Successful POST requests that create resources
- **204 No Content**: Successful DELETE requests

### Client Error Responses
- **400 Bad Request**: Invalid request format or parameters
- **401 Unauthorized**: Authentication required or failed
- **403 Forbidden**: Authenticated but not authorized
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation errors

### Server Error Responses
- **500 Internal Server Error**: Unexpected server errors

## Authentication Implementation

### AuthController Example

The `AuthController` demonstrates the standardized response format:

```php
/**
 * @group Authentication
 * 
 * APIs for managing user authentication
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * User Login
     * 
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: password123
     * 
     * @response 200 scenario="Successful login" {
     *   "code": 200,
     *   "message": "Login successful",
     *   "data": {
     *     "user": {...},
     *     "token": "1|abcdef123456789...",
     *     "token_type": "Bearer",
     *     "expires_in": 2592000
     *   },
     *   "pagination": {}
     * }
     */
    public function login(Request $request): JsonResponse
    {
        // Validation and authentication logic...
        
        return $this->successResponse($responseData, 'Login successful');
    }
}
```

## Scribe Documentation Integration

### Documentation Annotations

The API uses Scribe for generating comprehensive documentation <mcreference link="https://scribe.knuckles.wtf/laravel/documenting" index="0">0</mcreference>:

#### Group Annotation
```php
/**
 * @group Authentication
 * 
 * APIs for managing user authentication
 */
```

#### Endpoint Documentation
```php
/**
 * User Login
 * 
 * Authenticate a user and return an access token.
 * 
 * @bodyParam email string required The user's email address. Example: john@example.com
 * @bodyParam password string required The user's password. Example: password123
 * 
 * @response 200 scenario="Successful login" {...}
 * @response 401 scenario="Invalid credentials" {...}
 * @response 422 scenario="Validation error" {...}
 * 
 * @responseField code int HTTP status code
 * @responseField message string Response message
 * @responseField data.user object User information
 */
```

#### Parameter Documentation
- **`@bodyParam`**: Request body parameters
- **`@queryParam`**: URL query parameters
- **`@urlParam`**: URL path parameters

#### Response Documentation
- **`@response`**: Example responses with scenarios
- **`@responseField`**: Description of response fields

### Generating Documentation

```bash
# Generate API documentation
php artisan scribe:generate

# View documentation
# Visit: http://your-domain/docs
```

## Best Practices

### 1. Consistent Error Messages
```php
// Good: Descriptive and user-friendly
return $this->errorResponse('The provided credentials do not match our records', 401);

// Bad: Technical or vague
return $this->errorResponse('Auth failed', 401);
```

### 2. Proper HTTP Status Codes
```php
// Good: Appropriate status codes
return $this->successResponse($user, 'User created successfully', 201);
return $this->errorResponse('User not found', 404);

// Bad: Wrong status codes
return $this->successResponse($user, 'User created successfully', 200); // Should be 201
return $this->errorResponse('User not found', 400); // Should be 404
```

### 3. Validation Error Handling
```php
// Good: Use the validation error response method
$validator = Validator::make($request->all(), $rules);
if ($validator->fails()) {
    return $this->validationErrorResponse($validator->errors()->toArray());
}

// Bad: Manual error response
if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}
```

### 4. Pagination Implementation
```php
// Good: Consistent pagination format
$users = User::paginate($perPage);
$pagination = [
    'current_page' => $users->currentPage(),
    'per_page' => $users->perPage(),
    'total' => $users->total(),
    'last_page' => $users->lastPage(),
    'from' => $users->firstItem(),
    'to' => $users->lastItem(),
];
return $this->paginatedResponse($users->items(), $pagination);

// Bad: Inconsistent pagination
return response()->json($users->toArray());
```

### 5. Documentation Standards
```php
// Good: Comprehensive documentation
/**
 * Get Users List
 * 
 * Retrieve a paginated list of users with filtering options.
 * 
 * @queryParam page int Page number for pagination. Example: 1
 * @queryParam search string Search term for filtering. Example: john
 * 
 * @response 200 scenario="Success" {...}
 * @response 422 scenario="Validation error" {...}
 */

// Bad: Missing or incomplete documentation
/**
 * Get users
 */
```

## Testing API Responses

### Example Tests

```php
public function test_login_returns_standardized_response()
{
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password'
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'code',
                 'message',
                 'data' => [
                     'user',
                     'token',
                     'token_type',
                     'expires_in'
                 ],
                 'pagination'
             ]);
}

public function test_validation_error_returns_standardized_response()
{
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
             ->assertJsonStructure([
                 'code',
                 'message',
                 'data' => [
                     'errors'
                 ],
                 'pagination'
             ]);
}
```

## Migration Guide

### Converting Existing Controllers

1. **Add the ApiResponse trait:**
```php
use App\Traits\ApiResponse;

class YourController extends Controller
{
    use ApiResponse;
}
```

2. **Replace manual JSON responses:**
```php
// Before
return response()->json(['user' => $user], 200);

// After
return $this->successResponse($user, 'User retrieved successfully');
```

3. **Standardize error responses:**
```php
// Before
return response()->json(['error' => 'Not found'], 404);

// After
return $this->errorResponse('User not found', 404);
```

4. **Add Scribe documentation:**
```php
/**
 * @group Your Group Name
 * 
 * Description of your API group
 */
class YourController extends Controller
{
    /**
     * Endpoint Title
     * 
     * Detailed description of what this endpoint does.
     * 
     * @bodyParam field string required Description. Example: value
     * @response 200 scenario="Success" {...}
     */
    public function method(Request $request): JsonResponse
    {
        // Implementation
    }
}
```

## Conclusion

The standardized API response format provides:

- **Consistency**: All endpoints follow the same response structure
- **Predictability**: Clients can rely on consistent field names and types
- **Documentation**: Comprehensive API documentation with examples
- **Error Handling**: Standardized error responses with proper HTTP status codes
- **Maintainability**: Easy to maintain and extend with the ApiResponse trait

This implementation ensures a professional, consistent API experience that follows industry best practices and provides excellent developer experience for API consumers.