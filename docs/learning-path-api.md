# Learning Path API Documentation

This document provides comprehensive documentation for the Learning Path API implementation designed specifically for students in the learning center application.

## Overview

The Learning Path API provides a complete set of endpoints for users with institution-bound roles to:
- Browse available learning paths (accessible to school_teacher, school_admin, and student roles)
- View detailed information about learning paths and their courses
- Enroll in learning paths (with automatic course enrollment)
- Track their progress across learning paths and courses
- Filter and search learning paths based on various criteria

## Features Implemented

### 🔐 **Authentication & Authorization**
- **Sanctum Authentication**: All endpoints require valid API tokens
- **Role-based Access Control**: Only users with institution-bound roles (school_teacher, school_admin, student) can access learning paths
- **Universal Access**: Learning paths are not bound to institutions - all qualified users can access all learning paths

### 📚 **Learning Path Management**
- **List Learning Paths**: Paginated list with filtering and search capabilities
- **Detailed View**: Complete learning path information with course details
- **Enrollment System**: One-click enrollment in learning paths and associated courses
- **Progress Tracking**: Real-time progress monitoring across all enrolled learning paths

### 🔍 **Advanced Filtering & Search**
- **Role-based Filtering**: Automatic filtering by user's role (only institution-bound roles have access)
- **Search Functionality**: Search by learning path name or description
- **Enrollment Status Filtering**: Filter by enrolled, not enrolled, or all learning paths
- **Active Status Filtering**: Only shows active learning paths

### 📊 **Progress & Analytics**
- **Individual Progress**: Track progress for each course within learning paths
- **Completion Tracking**: Monitor completed lessons and overall progress
- **Enrollment History**: View enrollment dates and status changes

### 🔄 **Cursor-based Pagination**
- **Efficient Pagination**: Uses cursor-based pagination for better performance with large datasets
- **Consistent Results**: Provides stable pagination even when data changes
- **Forward/Backward Navigation**: Support for both next and previous page navigation
- **Encoded Cursors**: Uses encoded cursors for secure and efficient pagination

## API Endpoints

### 1. Get Learning Paths List
```http
GET /api/learning-paths
```

**Query Parameters:**
- `cursor` (string): Cursor for pagination (encoded cursor from previous response)
- `per_page` (int): Items per page (max 50, default 15)
- `search` (string): Search term for filtering
- `enrolled` (string): Filter by enrollment status (enrolled, not_enrolled, all)

**Response Example:**
```json
{
  "code": 200,
  "message": "Learning paths retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Full Stack Web Development",
      "slug": "full-stack-web-development",
      "description": "Complete web development learning path",
      "banner_url": "https://example.com/storage/banners/fullstack.jpg",
      "is_active": true,
      "total_estimated_time": 120,
      "courses_count": 8,
      "is_enrolled": true,
      "progress": 45.5,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "per_page": 15,
    "next_cursor": "eyJpZCI6MTUsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0",
    "prev_cursor": null,
    "has_more": true,
    "count": 15
  }
}
```

### 2. Get Learning Path Details
```http
GET /api/learning-paths/{id}
```

**Response Example:**
```json
{
  "code": 200,
  "message": "Learning path details retrieved successfully",
  "data": {
    "id": 1,
    "name": "Full Stack Web Development",
    "slug": "full-stack-web-development",
    "description": "Complete web development learning path",
    "banner_url": "https://example.com/storage/banners/fullstack.jpg",
    "is_active": true,
    "total_estimated_time": 120,
    "courses_count": 8,
    "is_enrolled": true,
    "progress": 45.5,
    "courses": [
      {
        "id": 1,
        "title": "HTML & CSS Fundamentals",
        "slug": "html-css-fundamentals",
        "description": "Learn the basics of HTML and CSS",
        "banner_url": "https://example.com/storage/banners/html-css.jpg",
        "estimated_time": 15,
        "is_published": true,
        "order_index": 1,
        "lessons_count": 12,
        "user_progress": {
          "is_enrolled": true,
          "progress": 75.0,
          "completed_lessons": 9,
          "total_lessons": 12
        },
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "enrollment": {
      "enrolled_at": "2024-01-15T10:30:00.000000Z",
      "progress": 45.5,
      "status": "active"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "pagination": {}
}
```

### 3. Enroll in Learning Path
```http
POST /api/learning-paths/{id}/enroll
```

**Response Example:**
```json
{
  "code": 201,
  "message": "Successfully enrolled in learning path",
  "data": {
    "learning_path_id": 1,
    "user_id": 5,
    "enrolled_at": "2024-01-15T10:30:00.000000Z",
    "progress": 0,
    "status": "active",
    "courses_enrolled": 8
  },
  "pagination": {}
}
```

### 4. Get Learning Path Progress
```http
GET /api/learning-paths/progress/my
```

**Query Parameters:**
- `cursor` (string): Cursor for pagination (encoded cursor from previous response)
- `per_page` (int): Items per page (max 50, default 15)

**Response Example:**
```json
{
  "code": 200,
  "message": "Learning path progress retrieved successfully",
  "data": [
    {
      "learning_path": {
        "id": 1,
        "name": "Full Stack Web Development",
        "slug": "full-stack-web-development",
        "banner_url": "https://example.com/storage/banners/fullstack.jpg",
        "total_estimated_time": 120,
        "courses_count": 8
      },
      "enrollment": {
        "enrolled_at": "2024-01-15T10:30:00.000000Z",
        "progress": 45.5,
        "status": "active"
      },
      "course_progress": [
        {
          "course_id": 1,
          "course_title": "HTML & CSS Fundamentals",
          "progress": 75.0,
          "completed_lessons": 9,
          "total_lessons": 12,
          "status": "in_progress"
        }
      ]
    }
  ],
  "pagination": {
    "per_page": 15,
    "next_cursor": null,
    "prev_cursor": null,
    "has_more": false,
    "count": 3
  }
}
```

## Database Schema

### Learning Paths Table
```sql
CREATE TABLE learning_paths (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    banner VARCHAR(255),
    description TEXT NOT NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_learning_paths_is_active ON learning_paths(is_active);
```

### Enrollments Table (Enhanced)
```sql
ALTER TABLE enrollments ADD COLUMN learning_path_id BIGINT REFERENCES learning_paths(id) ON DELETE CASCADE;
CREATE INDEX idx_enrollments_learning_path_id ON enrollments(learning_path_id);
```

### Learning Path Course Pivot Table
```sql
CREATE TABLE learning_path_course (
    learning_path_id BIGINT REFERENCES learning_paths(id) ON DELETE CASCADE,
    course_id BIGINT REFERENCES courses(id) ON DELETE CASCADE,
    order_index INTEGER NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    PRIMARY KEY (learning_path_id, course_id)
);
```

## Model Relationships

### LearningPath Model
```php
class LearningPath extends Model
{
    // Relationships
    public function courses(): BelongsToMany
    public function enrollments(): HasMany
    public function students(): BelongsToMany
    
    // Query Scopes
    public function scopeActive($query)
    public function scopeAccessibleByUser($query, $user)
    
    // Helper Methods
    public function getTotalEstimatedTimeAttribute(): int
    public function getProgressForUser($userId): float
    public function isUserEnrolled($userId): bool
}
```

### Enhanced Enrollment Model
```php
class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'learning_path_id', // New field
        'enrollment_status',
        'progress',
        'enrolled_at',
    ];
    
    // Relationships
    public function user(): BelongsTo
    public function course(): BelongsTo
    public function learningPath(): BelongsTo // New relationship
}
```

## Controller Implementation

### LearningPathController Features
- **Standardized API Responses**: Uses ApiResponse trait for consistent formatting
- **Authentication Required**: All methods require valid Sanctum tokens
- **Institution-based Filtering**: Automatic filtering by user's institution
- **Comprehensive Error Handling**: Proper HTTP status codes and error messages
- **Detailed Documentation**: Complete Scribe documentation with examples

### Key Methods
1. **`index()`**: List learning paths with filtering and pagination
2. **`show()`**: Get detailed learning path information
3. **`enroll()`**: Enroll user in learning path and associated courses
4. **`progress()`**: Get user's learning path progress

## Security Features

### Access Control
- **Role-based Access Control**: Only users with institution-bound roles (school_teacher, school_admin, student) can access learning paths
- **Universal Learning Paths**: Learning paths are accessible to all qualified users regardless of institution
- **Active Status Filtering**: Only active learning paths are shown
- **Authentication Required**: All endpoints require valid API tokens

### Data Validation
- **Input Sanitization**: All user inputs are validated and sanitized
- **SQL Injection Prevention**: Uses Eloquent ORM for database queries
- **XSS Protection**: Proper output encoding for all responses

## Testing

### Comprehensive Test Suite
The API includes a complete test suite covering:

1. **Authentication Tests**
   - Authenticated access to all endpoints
   - Unauthenticated access rejection

2. **Institution Isolation Tests**
   - Students can only see their institution's learning paths
   - Cross-institution access prevention

3. **Filtering and Search Tests**
   - Search functionality
   - Enrollment status filtering
   - Pagination support

4. **Enrollment Tests**
   - Successful enrollment process
   - Duplicate enrollment prevention
   - Automatic course enrollment

5. **Progress Tracking Tests**
   - Progress calculation accuracy
   - Course completion tracking

### Running Tests
```bash
# Run all Learning Path API tests
php artisan test tests/Feature/LearningPathApiTest.php

# Run specific test
php artisan test --filter="student_can_get_learning_paths_list"
```

## Usage Examples

### Authentication
```javascript
// Get authentication token
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
```

### Fetching Learning Paths
```javascript
// Get first page of learning paths
const response = await fetch('/api/learning-paths?search=programming&per_page=10', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const learningPaths = await response.json();

// Get next page using cursor
if (learningPaths.pagination.has_more) {
  const nextResponse = await fetch(`/api/learning-paths?cursor=${learningPaths.pagination.next_cursor}&per_page=10`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const nextPage = await nextResponse.json();
}
```

### Enrolling in Learning Path
```javascript
// Enroll in a learning path
const enrollResponse = await fetch(`/api/learning-paths/${learningPathId}/enroll`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const enrollment = await enrollResponse.json();
```

### Tracking Progress
```javascript
// Get progress for all enrolled learning paths
const progressResponse = await fetch('/api/learning-paths/progress/my', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const progress = await progressResponse.json();
```

## Performance Considerations

### Database Optimization
- **Proper Indexing**: Indexes on frequently queried columns
- **Eager Loading**: Optimized queries with proper relationships
- **Query Scopes**: Efficient filtering at database level
- **Pagination**: Prevents memory issues with large datasets

### Caching Strategy
- **Model Caching**: Cache frequently accessed learning path data
- **Query Result Caching**: Cache expensive aggregation queries
- **API Response Caching**: Cache stable API responses

### Example Caching Implementation
```php
// Cache learning path list for institution
$learningPaths = Cache::remember(
    "learning_paths_institution_{$user->institution_id}",
    3600, // 1 hour
    function () use ($user) {
        return LearningPath::accessibleByStudent($user)->get();
    }
);
```

## Error Handling

### Standard Error Responses
All endpoints return standardized error responses:

```json
{
  "code": 404,
  "message": "Learning path not found or not accessible",
  "data": [],
  "pagination": {}
}
```

### Common Error Codes
- **401**: Unauthenticated - Invalid or missing token
- **403**: Forbidden - Access denied to resource
- **404**: Not Found - Learning path doesn't exist or not accessible
- **400**: Bad Request - Already enrolled or invalid request
- **422**: Validation Error - Invalid input data
- **500**: Server Error - Internal server error

## Future Enhancements

### Planned Features
1. **Learning Path Recommendations**: AI-powered learning path suggestions
2. **Social Features**: Learning path sharing and collaboration
3. **Advanced Analytics**: Detailed progress analytics and insights
4. **Mobile App Support**: Enhanced mobile API endpoints
5. **Offline Support**: Downloadable learning path content

### API Versioning
```php
// Future API versioning structure
Route::prefix('v2')->group(function () {
    Route::get('/learning-paths', [LearningPathV2Controller::class, 'index']);
    // ... other v2 endpoints
});
```

## Conclusion

The Learning Path API provides a comprehensive, secure, and well-documented solution for students to interact with learning paths in the learning center application. Key benefits include:

- **Student-Focused Design**: Tailored specifically for student needs
- **Institution Security**: Proper isolation and access control
- **Comprehensive Features**: Complete learning path management
- **Excellent Documentation**: Detailed API documentation with examples
- **Robust Testing**: Comprehensive test coverage
- **Performance Optimized**: Efficient queries and caching strategies

The implementation follows Laravel best practices and provides a solid foundation for future enhancements and scaling.