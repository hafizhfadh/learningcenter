# Institution API Documentation

This document provides comprehensive documentation for the Institution API endpoint that allows students and other institution-bound users to retrieve their institution information.

## Overview

The Institution API provides a secure endpoint for authenticated users with institution-bound roles to retrieve information about their associated institution. This endpoint is designed specifically for users who belong to educational institutions and need access to institutional data.

## Features

### 🔐 **Authentication & Authorization**
- **Sanctum Authentication**: Requires valid API tokens
- **Role-based Access Control**: Only users with institution-bound roles can access
- **Institution Validation**: Ensures users can only access their own institution data

### 🏫 **Institution Information**
- **Complete Institution Details**: Name, slug, domain, and settings
- **Configuration Data**: Institution-specific settings and preferences
- **Metadata**: Creation and update timestamps

### 🛡️ **Security Features**
- **Access Control**: Restricted to institution-bound roles only
- **Data Isolation**: Users can only access their own institution
- **Input Validation**: Proper authentication and authorization checks

## API Endpoint

### Get Institution Information
```http
GET /api/institution
```

**Authentication Required**: Yes (Bearer token)

**Accessible Roles**:
- `student` - Students enrolled in the institution
- `school_teacher` - Teachers working at the institution  
- `school_admin` - Administrative staff of the institution

**Response Example:**
```json
{
  "code": 200,
  "message": "Institution information retrieved successfully",
  "data": {
    "id": 1,
    "name": "Harvard University",
    "slug": "harvard-university",
    "domain": "harvard.edu",
    "settings": {
      "timezone": "America/New_York",
      "academic_year": "2024-2025",
      "contact_email": "admin@harvard.edu",
      "phone": "+1-617-495-1000",
      "address": "Cambridge, MA 02138",
      "website": "https://www.harvard.edu"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "pagination": {}
}
```

## Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `code` | integer | HTTP status code |
| `message` | string | Response message |
| `data` | object | Institution information |
| `data.id` | integer | Institution unique identifier |
| `data.name` | string | Institution full name |
| `data.slug` | string | Institution URL-friendly identifier |
| `data.domain` | string | Institution email domain |
| `data.settings` | object | Institution configuration and settings |
| `data.created_at` | string | Institution creation timestamp |
| `data.updated_at` | string | Institution last update timestamp |
| `pagination` | object | Pagination information (empty for this endpoint) |

## Error Responses

### 401 Unauthenticated
```json
{
  "code": 401,
  "message": "Unauthenticated",
  "data": [],
  "pagination": {}
}
```

### 403 Access Denied
```json
{
  "code": 403,
  "message": "Access denied. Only users with institution-bound roles can access institution information",
  "data": [],
  "pagination": {}
}
```

### 404 No Institution Found
```json
{
  "code": 404,
  "message": "No institution found for this user",
  "data": [],
  "pagination": {}
}
```

## Usage Examples

### JavaScript/Frontend
```javascript
// Get authentication token first
const loginResponse = await fetch('/api/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'student@university.edu',
    password: 'password'
  })
});

const { data } = await loginResponse.json();
const token = data.token;

// Get institution information
const institutionResponse = await fetch('/api/institution', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const institution = await institutionResponse.json();

if (institution.code === 200) {
  console.log('Institution:', institution.data.name);
  console.log('Domain:', institution.data.domain);
  console.log('Settings:', institution.data.settings);
} else {
  console.error('Error:', institution.message);
}
```

### cURL
```bash
# Get authentication token
TOKEN=$(curl -s -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "student@university.edu", "password": "password"}' \
  | jq -r '.data.token')

# Get institution information
curl -X GET http://localhost/api/institution \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### PHP/Laravel
```php
use Illuminate\Support\Facades\Http;

// Get authentication token
$loginResponse = Http::post('/api/login', [
    'email' => 'student@university.edu',
    'password' => 'password'
]);

$token = $loginResponse->json()['data']['token'];

// Get institution information
$institutionResponse = Http::withToken($token)
    ->get('/api/institution');

$institution = $institutionResponse->json();

if ($institution['code'] === 200) {
    $institutionData = $institution['data'];
    echo "Institution: " . $institutionData['name'];
    echo "Domain: " . $institutionData['domain'];
}
```

## Access Control Matrix

| Role | Institution Access | Description |
|------|-------------------|-------------|
| `student` | ✅ Own Institution | Students can access their institution's information |
| `school_teacher` | ✅ Own Institution | Teachers can access their institution's information |
| `school_admin` | ✅ Own Institution | Admins can access their institution's information |
| `super_admin` | ❌ No Access | Super admins are not bound to institutions |
| `panel_user` | ❌ No Access | Panel users are not bound to institutions |

## Security Considerations

### Authentication
- All requests must include a valid Bearer token
- Tokens are generated through the `/api/login` endpoint
- Tokens can be refreshed using the `/api/refresh` endpoint

### Authorization
- Only users with institution-bound roles can access this endpoint
- Users can only access information about their own institution
- Cross-institution access is prevented

### Data Protection
- Institution settings may contain sensitive configuration data
- Access is logged and monitored
- Rate limiting applies to prevent abuse

## Implementation Details

### Controller Method
```php
public function institution(Request $request): JsonResponse
{
    $user = $request->user();

    // Check if user has institution-bound roles
    $institutionBoundRoles = ['school_teacher', 'school_admin', 'student'];
    $hasInstitutionBoundRole = $user->roles()->whereIn('name', $institutionBoundRoles)->exists();

    if (!$hasInstitutionBoundRole) {
        return $this->errorResponse(
            'Access denied. Only users with institution-bound roles can access institution information',
            403
        );
    }

    // Check if user has an institution
    if (!$user->institution_id) {
        return $this->errorResponse('No institution found for this user', 404);
    }

    // Load the institution with its settings
    $institution = $user->institution;

    if (!$institution) {
        return $this->errorResponse('No institution found for this user', 404);
    }

    $institutionData = [
        'id' => $institution->id,
        'name' => $institution->name,
        'slug' => $institution->slug,
        'domain' => $institution->domain,
        'settings' => $institution->settings ?? [],
        'created_at' => $institution->created_at,
        'updated_at' => $institution->updated_at,
    ];

    return $this->successResponse($institutionData, 'Institution information retrieved successfully');
}
```

### Route Definition
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/institution', [\App\Http\Controllers\API\AuthController::class, 'institution']);
});
```

## Testing

### Unit Tests
```php
public function test_student_can_access_institution_information()
{
    $institution = Institution::factory()->create();
    $student = User::factory()->create(['institution_id' => $institution->id]);
    $student->assignRole('student');
    
    Sanctum::actingAs($student);
    
    $response = $this->getJson('/api/institution');
    
    $response->assertStatus(200)
             ->assertJson([
                 'code' => 200,
                 'message' => 'Institution information retrieved successfully',
                 'data' => [
                     'id' => $institution->id,
                     'name' => $institution->name,
                     'slug' => $institution->slug,
                     'domain' => $institution->domain,
                 ]
             ]);
}

public function test_super_admin_cannot_access_institution_information()
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    
    Sanctum::actingAs($superAdmin);
    
    $response = $this->getJson('/api/institution');
    
    $response->assertStatus(403)
             ->assertJson([
                 'code' => 403,
                 'message' => 'Access denied. Only users with institution-bound roles can access institution information'
             ]);
}
```

### Manual Testing
```bash
# Test with student user
curl -X GET http://localhost/api/institution \
  -H "Authorization: Bearer STUDENT_TOKEN" \
  -H "Accept: application/json"

# Test with super admin (should fail)
curl -X GET http://localhost/api/institution \
  -H "Authorization: Bearer SUPER_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Test without authentication (should fail)
curl -X GET http://localhost/api/institution \
  -H "Accept: application/json"
```

## Performance Considerations

### Database Queries
- Single query to load user's institution
- Efficient relationship loading using Eloquent
- No N+1 query problems

### Caching Strategy
```php
// Example caching implementation
$institution = Cache::remember(
    "institution_{$user->institution_id}",
    3600, // 1 hour
    function () use ($user) {
        return $user->institution;
    }
);
```

### Rate Limiting
- Standard API rate limiting applies
- Consider implementing specific limits for institution data access
- Monitor for unusual access patterns

## Future Enhancements

### Planned Features
1. **Institution Statistics**: Add enrollment counts, course counts, etc.
2. **Institution Branding**: Include logo, colors, and branding information
3. **Contact Information**: Detailed contact information and office hours
4. **Academic Calendar**: Integration with institution's academic calendar
5. **Announcements**: Institution-wide announcements and news

### API Versioning
```php
// Future API versioning structure
Route::prefix('v2')->group(function () {
    Route::get('/institution', [InstitutionV2Controller::class, 'show']);
});
```

## Conclusion

The Institution API provides a secure and efficient way for institution-bound users to access their institution's information. The endpoint follows REST principles, implements proper authentication and authorization, and provides comprehensive error handling.

Key benefits include:
- **Secure Access**: Role-based access control ensures data security
- **Complete Information**: Provides all necessary institution details
- **Standardized Format**: Consistent with other API endpoints
- **Well Documented**: Comprehensive documentation and examples
- **Future Ready**: Extensible architecture for additional features

This API endpoint serves as a foundation for institution-aware features throughout the learning management system.