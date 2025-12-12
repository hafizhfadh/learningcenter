# API Response Standards

This document outlines the standardized API response format implemented across the learning center application, following consistent patterns and best practices for API development.

## Overview

All API endpoints in the learning center application follow a consistent response format that includes:
- **Standardized Structure**: Consistent JSON structure across all endpoints
- **HTTP Status Codes**: Proper HTTP status codes for different scenarios
- **Error Handling**: Comprehensive error response format
- **Pagination Support**: Built-in pagination for list endpoints

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

### Dual-token Authentication Overview

The authentication system uses a dual-token model with the following components:

- **Client APP_TOKEN header**: Identifies the calling application and is required when calling `POST /login`. This value is configured per client application (for example, a specific frontend or mobile app).
- **auth token (`token`)**: A standard Bearer token used for user authentication on protected routes via the `Authorization` header.
- **Enhanced `app_token`**: A signed token returned from `POST /login` which contains user-specific claims and is required for all protected routes in addition to the auth token.

The login response now includes both the `token` and `app_token` fields.

### Login Flow (AuthController Example)

The `AuthController` demonstrates the dual-token login flow:

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
     * This endpoint does not require a bearer token. Instead, client applications must
     * include a valid APP_TOKEN header identifying the calling application.
     * 
     * @headerParam APP_TOKEN string required Application access token identifying the client.
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
     *     "expires_in": 2592000,
     *     "app_token": "{enhanced_app_token}"
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

### Authentication Headers on Protected Endpoints

All protected API endpoints now require two headers:

- `Authorization: Bearer {token}` – The auth token returned from `POST /login`.
- `APP_TOKEN: {app_token}` – The enhanced app token returned from `POST /login` containing user claims.

Example (cURL):

```bash
curl -X GET "https://api.learning-center-academy.local/profile" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {auth_token}" \
  -H "APP_TOKEN: {enhanced_app_token}"
```

### Access Control Matrix

The following table summarizes which roles can access common endpoints (additional constraints may apply at the resource level):

| Endpoint                              | Requires Auth | Requires APP_TOKEN | Roles with Access                                      |
|---------------------------------------|---------------|--------------------|--------------------------------------------------------|
| `POST /login`                         | No            | Client APP_TOKEN   | All users with valid credentials                       |
| `POST /refresh`                       | Yes           | Yes                | Same as login, with valid auth token                   |
| `GET /profile`                        | Yes           | Yes                | `student`, `school_teacher`, `school_admin`, `super_admin` |
| `GET /institution`                    | Yes           | Yes                | Institution-bound roles (`student`, `school_teacher`, `school_admin`) |
| `GET /learning-paths`                 | Yes           | Yes                | Institution-bound roles                                |
| `GET /courses`                        | Yes           | Yes                | Institution-bound roles                                |
| Admin panel (Filament)                | Yes           | N/A                | `school_admin`, `school_teacher`, `super_admin`        |

Refer to the specific endpoint documentation for detailed role checks and business rules.

### Implementation Steps for Clients

1. **Obtain a client APP_TOKEN** from the platform administrator and configure it in your client application (for example via environment variables).
2. **Call `POST /login`** with:
   - Request body: `email`, `password`.
   - Header: `APP_TOKEN: {client-app-token}`.
3. **Store the returned tokens** securely:
   - `data.token` – Use as a Bearer token in the `Authorization` header.
   - `data.app_token` – Use as the `APP_TOKEN` header for protected routes.
4. **Call protected endpoints** with both headers:
   - `Authorization: Bearer {token}`.
   - `APP_TOKEN: {app_token}`.
5. **Handle expiration**:
   - If you receive `401 Unauthorized` or `403 Forbidden` related to tokens, prompt the user to log in again.

### Troubleshooting Authentication Issues

Common issues and resolutions:

- **401 Unauthorized (missing or invalid token)**  
  - Verify that the `Authorization` header is present and correctly formatted.  
  - Ensure the `APP_TOKEN` header is present on protected routes.  
  - Check that the enhanced `app_token` was not truncated or modified in transit.

- **403 Forbidden (expired or unauthorized app_token)**  
  - The enhanced `app_token` may be expired; call `POST /login` again to obtain a new one.  
  - Verify that the client APP_TOKEN is correctly configured for your application.

- **Subject mismatch (user ID does not match)**  
  - Ensure that you are not reusing an `app_token` issued for a different user.  
  - Always update stored tokens after a successful login.

### Migration Guide: From Single-token to Dual-token Authentication

If you previously used only a Bearer token for authentication:

1. **Update your login request** to include the client APP_TOKEN header:
   ```bash
   curl -X POST "https://api.learning-center-academy.local/login" \
     -H "Accept: application/json" \
     -H "APP_TOKEN: {client-app-token}" \
     -d '{"email": "john@example.com", "password": "password123"}'
   ```
2. **Update your token storage** to keep both `token` and `app_token` from the login response.
3. **Update all protected requests** to send both headers:
   - Before:  
     `Authorization: Bearer {token}`
   - After:  
     `Authorization: Bearer {token}`  
     `APP_TOKEN: {app_token}`
4. **Review role-based access rules** for each endpoint and update your client-side navigation or feature toggles accordingly.

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

## Conclusion

The standardized API response format provides:

- **Consistency**: All endpoints follow the same response structure
- **Predictability**: Clients can rely on consistent field names and types
- **Documentation**: API documentation with examples
- **Error Handling**: Standardized error responses with proper HTTP status codes
- **Maintainability**: Easy to maintain and extend with the ApiResponse trait

This implementation ensures a professional, consistent API experience that follows industry best practices and provides excellent developer experience for API consumers.
