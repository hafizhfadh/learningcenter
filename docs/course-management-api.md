# Course Management API Documentation

## Overview

The Course Management API provides comprehensive endpoints for managing and accessing course information in the learning center system. This API supports course listing, searching, and detailed course information retrieval with role-based access control.

## Base URL
```
https://your-domain.com/api
```

In production deployments, configure the Laravel backend with:

- `APP_URL=https://your-domain.com`
- `API_URL=https://api.your-domain.com` (if using a separate API subdomain)
- `FORCE_HTTPS=true`

When using a separate frontend domain, ensure the backend CORS configuration
allows the frontend origin via the `CORS_ALLOWED_ORIGINS` environment variable, for example:

```
CORS_ALLOWED_ORIGINS=https://app.your-domain.com,https://your-domain.com
```

## Authentication

All endpoints require authentication using Laravel Sanctum tokens. Include the token in the Authorization header:

```
Authorization: Bearer {your-token}
```

## Endpoints

### 1. Course Listing

**Endpoint:** `GET /api/courses`

**Description:** Retrieve a paginated list of courses with filtering and sorting capabilities.

**Access Control:**
- **Students:** Can only see published courses
- **Teachers/Admins:** Can see all courses (published and unpublished)

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number for pagination |
| `per_page` | integer | 20 | Number of items per page (max: 100) |
| `sort` | string | created_at | Sort field: `title`, `created_at`, `estimated_time` |
| `order` | string | desc | Sort order: `asc` or `desc` |

**Response Structure:**
```json
{
  "code": 200,
  "message": "Courses retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Introduction to Programming",
      "slug": "intro-programming",
      "description": "Learn the basics of programming",
      "banner_url": "https://domain.com/storage/banners/course1.jpg",
      "tags": "programming,basics,beginner",
      "estimated_time": 120,
      "is_published": true,
      "created_at": "2024-01-15T10:00:00Z",
      "instructor": {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "enrollment_status": "not_enrolled",
      "total_lessons": 12,
      "total_tasks": 8
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3,
    "from": 1,
    "to": 20,
    "has_more_pages": true
  }
}
```

**Example Requests:**

```bash
# Basic listing
curl -H "Authorization: Bearer {token}" \
     "https://your-domain.com/api/courses"

# With pagination and sorting
curl -H "Authorization: Bearer {token}" \
     "https://your-domain.com/api/courses?page=2&per_page=10&sort=title&order=asc"
```

**JavaScript Example:**
```javascript
const response = await fetch('/api/courses?page=1&per_page=20', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();
```

### 2. Course Search

**Endpoint:** `GET /api/courses/search`

**Description:** Search and filter courses with advanced criteria and relevance scoring.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `q` | string | Search query (searches title and description) |
| `instructor` | string | Filter by instructor name |
| `tags` | string | Filter by tags (comma-separated) |
| `start_date` | date | Filter courses created after this date (YYYY-MM-DD) |
| `end_date` | date | Filter courses created before this date (YYYY-MM-DD) |
| `min_time` | integer | Minimum estimated time in minutes |
| `max_time` | integer | Maximum estimated time in minutes |
| `page` | integer | Page number for pagination |
| `per_page` | integer | Number of items per page (max: 100) |
| `sort` | string | Sort by: `relevance`, `title`, `created_at`, `estimated_time` |
| `order` | string | Sort order: `asc` or `desc` |

**Response Structure:**
```json
{
  "code": 200,
  "message": "Search completed successfully",
  "data": [
    {
      "id": 1,
      "title": "Introduction to Programming",
      "slug": "intro-programming",
      "description": "Learn the basics of programming",
      "banner_url": "https://domain.com/storage/banners/course1.jpg",
      "tags": "programming,basics,beginner",
      "estimated_time": 120,
      "is_published": true,
      "created_at": "2024-01-15T10:00:00Z",
      "instructor": {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "enrollment_status": "not_enrolled",
      "total_lessons": 12,
      "total_tasks": 8,
      "relevance_score": 95.5
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 5,
    "last_page": 1,
    "from": 1,
    "to": 5,
    "has_more_pages": false
  }
}
```

**Example Requests:**

```bash
# Text search
curl -H "Authorization: Bearer {token}" \
     "https://your-domain.com/api/courses/search?q=programming"

# Advanced filtering
curl -H "Authorization: Bearer {token}" \
     "https://your-domain.com/api/courses/search?instructor=John%20Doe&tags=beginner&min_time=60&max_time=180"

# Date range search
curl -H "Authorization: Bearer {token}" \
     "https://your-domain.com/api/courses/search?start_date=2024-01-01&end_date=2024-12-31"
```

**JavaScript Example:**
```javascript
const searchParams = new URLSearchParams({
  q: 'programming',
  tags: 'beginner,basics',
  min_time: '60',
  max_time: '180',
  sort: 'relevance'
});

const response = await fetch(`/api/courses/search?${searchParams}`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();
```

### 3. Course Details

**Endpoint:** `GET /api/courses/{courseId}`

**Description:** Retrieve detailed information about a specific course, including lessons, tasks, and student progress.

**Access Control:**
- **Students:** Can only access published courses
- **Teachers/Admins:** Can access all courses
- **Enrolled Students:** Get additional progress and enrollment information

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `courseId` | integer | The unique identifier of the course |

**Response Structure:**
```json
{
  "code": 200,
  "message": "Course details retrieved successfully",
  "data": {
    "id": 1,
    "title": "Introduction to Programming",
    "slug": "intro-programming",
    "description": "Learn the basics of programming with hands-on exercises",
    "banner_url": "https://domain.com/storage/banners/course1.jpg",
    "tags": "programming,basics,beginner",
    "estimated_time": 120,
    "is_published": true,
    "created_at": "2024-01-15T10:00:00Z",
    "updated_at": "2024-01-20T15:30:00Z",
    "instructor": {
      "id": 2,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "teachers": [
      {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com",
        "assigned_at": "2024-01-15T10:00:00Z"
      }
    ],
    "enrollment_status": "active",
    "enrollment_date": "2024-01-16T09:00:00Z",
    "progress_percentage": 65.5,
    "lessons": [
      {
        "id": 1,
        "title": "Getting Started",
        "slug": "getting-started",
        "order_index": 1,
        "estimated_time": 30,
        "is_completed": true,
        "completed_at": "2024-01-17T14:00:00Z"
      },
      {
        "id": 2,
        "title": "Variables and Data Types",
        "slug": "variables-data-types",
        "order_index": 2,
        "estimated_time": 45,
        "is_completed": false,
        "completed_at": null
      }
    ],
    "lesson_sections": [
      {
        "id": 1,
        "title": "Introduction",
        "order_index": 1,
        "lessons": [
          {
            "id": 1,
            "title": "Getting Started",
            "slug": "getting-started",
            "order_index": 1,
            "estimated_time": 30,
            "is_completed": true
          }
        ]
      }
    ],
    "tasks": [
      {
        "id": 1,
        "title": "Hello World Assignment",
        "description": "Create your first program",
        "type": "assignment",
        "is_completed": true,
        "completed_at": "2024-01-17T16:00:00Z"
      }
    ],
    "learning_paths": [
      {
        "id": 1,
        "title": "Web Development Track",
        "slug": "web-development-track"
      }
    ],
    "statistics": {
      "total_lessons": 12,
      "completed_lessons": 8,
      "total_tasks": 8,
      "completed_tasks": 6,
      "total_enrolled_students": 45,
      "average_completion_rate": 72.3
    }
  },
  "pagination": null
}
```

**Example Requests:**

```bash
# Get course details
curl -H "Authorization: Bearer {token}" \
     "https://your-domain.com/api/courses/1"
```

**JavaScript Example:**
```javascript
const response = await fetch('/api/courses/1', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const courseData = await response.json();
```

## Error Responses

### Common Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 401 | Unauthenticated | Missing or invalid authentication token |
| 403 | Access denied | Insufficient permissions or unpublished course access |
| 404 | Course not found | Course with specified ID doesn't exist |
| 422 | Validation error | Invalid query parameters |
| 429 | Too many requests | Rate limit exceeded |
| 500 | Internal server error | Server-side error |

### Error Response Format

```json
{
  "code": 404,
  "message": "Course not found",
  "data": null,
  "pagination": null
}
```

### Validation Error Response

```json
{
  "code": 422,
  "message": "Validation failed",
  "data": {
    "errors": {
      "per_page": ["The per_page field must not be greater than 100."],
      "start_date": ["The start_date field must be a valid date."]
    }
  },
  "pagination": null
}
```

## Access Control Matrix

| Role | Course Listing | Search | Published Course Details | Unpublished Course Details |
|------|----------------|--------|--------------------------|----------------------------|
| Student | ✅ (Published only) | ✅ (Published only) | ✅ | ❌ |
| School Teacher | ✅ (All courses) | ✅ (All courses) | ✅ | ✅ |
| School Admin | ✅ (All courses) | ✅ (All courses) | ✅ | ✅ |
| Super Admin | ✅ (All courses) | ✅ (All courses) | ✅ | ✅ |

## Rate Limiting

- **Students:** 60 requests per minute
- **Teachers/Admins:** 120 requests per minute
- **Search endpoints:** Additional 30 requests per minute limit

## Performance Considerations

1. **Pagination:** Always use pagination for large datasets
2. **Caching:** Course data is cached for 15 minutes
3. **Search:** Full-text search is optimized with database indexes
4. **Eager Loading:** Related data is efficiently loaded to prevent N+1 queries

## Usage Examples

### React Component Example

```jsx
import React, { useState, useEffect } from 'react';

const CourseList = () => {
  const [courses, setCourses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [pagination, setPagination] = useState({});

  useEffect(() => {
    fetchCourses();
  }, []);

  const fetchCourses = async (page = 1) => {
    try {
      const response = await fetch(`/api/courses?page=${page}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json'
        }
      });
      
      const data = await response.json();
      
      if (data.code === 200) {
        setCourses(data.data);
        setPagination(data.pagination);
      }
    } catch (error) {
      console.error('Error fetching courses:', error);
    } finally {
      setLoading(false);
    }
  };

  const searchCourses = async (query) => {
    try {
      const response = await fetch(`/api/courses/search?q=${encodeURIComponent(query)}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json'
        }
      });
      
      const data = await response.json();
      
      if (data.code === 200) {
        setCourses(data.data);
        setPagination(data.pagination);
      }
    } catch (error) {
      console.error('Error searching courses:', error);
    }
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      <h1>Courses</h1>
      {courses.map(course => (
        <div key={course.id} className="course-card">
          <h3>{course.title}</h3>
          <p>{course.description}</p>
          <p>Instructor: {course.instructor.name}</p>
          <p>Status: {course.enrollment_status}</p>
          <p>Lessons: {course.total_lessons} | Tasks: {course.total_tasks}</p>
        </div>
      ))}
    </div>
  );
};

export default CourseList;
```

### PHP/Laravel Client Example

```php
<?php

use Illuminate\Support\Facades\Http;

class CourseApiClient
{
    private $baseUrl;
    private $token;

    public function __construct($baseUrl, $token)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }

    public function getCourses($page = 1, $perPage = 20)
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/api/courses", [
                'page' => $page,
                'per_page' => $perPage
            ]);

        return $response->json();
    }

    public function searchCourses($query, $filters = [])
    {
        $params = array_merge(['q' => $query], $filters);
        
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/api/courses/search", $params);

        return $response->json();
    }

    public function getCourseDetails($courseId)
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/api/courses/{$courseId}");

        return $response->json();
    }
}

// Usage
$client = new CourseApiClient('https://your-domain.com', $authToken);

// Get courses
$courses = $client->getCourses(1, 20);

// Search courses
$searchResults = $client->searchCourses('programming', [
    'tags' => 'beginner',
    'min_time' => 60,
    'max_time' => 180
]);

// Get course details
$courseDetails = $client->getCourseDetails(1);
```

## Testing

### Unit Tests
Run the comprehensive test suite:

```bash
php artisan test tests/Feature/CourseApiTest.php
```

### Manual Testing with cURL

```bash
# Set your token
TOKEN="your-auth-token-here"

# Test course listing
curl -H "Authorization: Bearer $TOKEN" \
     -H "Accept: application/json" \
     "http://localhost/api/courses"

# Test search
curl -H "Authorization: Bearer $TOKEN" \
     -H "Accept: application/json" \
     "http://localhost/api/courses/search?q=programming&tags=beginner"

# Test course details
curl -H "Authorization: Bearer $TOKEN" \
     -H "Accept: application/json" \
     "http://localhost/api/courses/1"
```

## Security Considerations

1. **Authentication:** All endpoints require valid Sanctum tokens
2. **Authorization:** Role-based access control prevents unauthorized access
3. **Input Validation:** All query parameters are validated and sanitized
4. **Rate Limiting:** Prevents API abuse and ensures fair usage
5. **Data Filtering:** Students can only access published courses
6. **SQL Injection Prevention:** Uses Eloquent ORM and parameter binding
7. **XSS Prevention:** All output is properly escaped

## Changelog

### Version 1.0.0 (2024-01-20)
- Initial release of Course Management API
- Course listing with pagination and sorting
- Advanced search functionality with relevance scoring
- Detailed course information with progress tracking
- Comprehensive role-based access control
- Full test coverage and documentation
