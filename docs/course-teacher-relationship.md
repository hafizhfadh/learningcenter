# Course-Teacher Relationship Implementation

This document outlines the implementation of the many-to-many relationship between Courses and Teachers in the learning center system.

## Overview

The Course-Teacher relationship allows:
- Multiple teachers to be assigned to a single course
- A single teacher to be assigned to multiple courses
- Tracking of assignment timestamps
- Role-based access control for course management

## Database Structure

### Migration: `course_teachers` Pivot Table

**File**: `database/migrations/2025_10_31_095821_create_course_teachers_table.php`

```php
Schema::create('course_teachers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
    $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
    $table->timestamp('assigned_at')->nullable();
    $table->timestamps();

    // Ensure unique course-teacher combinations
    $table->unique(['course_id', 'teacher_id']);
    
    // Add indexes for better query performance
    $table->index('course_id');
    $table->index('teacher_id');
});
```

### Key Features:
- **Foreign Key Constraints**: Ensures data integrity with cascade deletes
- **Unique Constraint**: Prevents duplicate teacher assignments to the same course
- **Assignment Tracking**: `assigned_at` timestamp for tracking when teachers were assigned
- **Performance Indexes**: Optimized queries for course and teacher lookups

## Model Relationships

### Course Model

**File**: `app/Models/Course.php`

```php
/**
 * The teachers assigned to this course.
 * 
 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
 */
public function teachers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'course_teachers', 'course_id', 'teacher_id')
        ->withPivot('assigned_at')
        ->withTimestamps()
        ->whereHas('roles', function ($query) {
            $query->where('name', 'school_teacher');
        });
}
```

**Features**:
- **Role Filtering**: Only returns users with the 'school_teacher' role
- **Pivot Data**: Includes `assigned_at` timestamp and standard timestamps
- **Type Hints**: Proper return type annotation for IDE support

### User Model

**File**: `app/Models/User.php`

```php
/**
 * Get the courses assigned to this teacher.
 * Only available for users with the 'school_teacher' role.
 * 
 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
 */
public function courses(): BelongsToMany
{
    return $this->belongsToMany(Course::class, 'course_teachers', 'teacher_id', 'course_id')
        ->withPivot('assigned_at')
        ->withTimestamps();
}

/**
 * Get the courses assigned to this teacher (alias for courses()).
 * This provides a more semantic method name for teacher-specific contexts.
 * 
 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
 */
public function teachingCourses(): BelongsToMany
{
    return $this->courses();
}
```

**Features**:
- **Inverse Relationship**: Allows teachers to access their assigned courses
- **Semantic Alias**: `teachingCourses()` provides clearer context
- **Consistent Pivot Data**: Same pivot fields as the Course relationship

## Usage Examples

### Assigning Teachers to Courses

```php
// Get a course and teacher
$course = Course::find(1);
$teacher = User::whereHas('roles', function($q) {
    $q->where('name', 'school_teacher');
})->first();

// Assign teacher to course with timestamp
$course->teachers()->attach($teacher->id, [
    'assigned_at' => now()
]);

// Or using sync for multiple teachers
$teacherIds = [1, 2, 3];
$course->teachers()->sync($teacherIds);
```

### Retrieving Course Teachers

```php
// Get all teachers for a course
$course = Course::with('teachers')->find(1);
$teachers = $course->teachers;

// Get teachers with pivot data
foreach ($course->teachers as $teacher) {
    echo $teacher->name;
    echo $teacher->pivot->assigned_at;
    echo $teacher->pivot->created_at;
}

// Check if a specific teacher is assigned
$isAssigned = $course->teachers()->where('teacher_id', $teacherId)->exists();
```

### Retrieving Teacher's Courses

```php
// Get all courses for a teacher
$teacher = User::with('courses')->find(1);
$courses = $teacher->courses;

// Using the semantic alias
$teachingCourses = $teacher->teachingCourses;

// Get courses with pivot data
foreach ($teacher->courses as $course) {
    echo $course->title;
    echo $course->pivot->assigned_at;
}
```

### Removing Teacher Assignments

```php
// Remove specific teacher from course
$course->teachers()->detach($teacherId);

// Remove all teachers from course
$course->teachers()->detach();

// Remove teacher from all courses
$teacher->courses()->detach();
```

## Access Control Integration

### HasShieldPermissions Trait Updates

The relationship is integrated with the Shield permission system for proper access control:

```php
// Course access check for teachers
if ($user->hasRole('school_teacher')) {
    // Teachers can access courses they are assigned to or courses in their institution
    return $record->teachers()->where('teacher_id', $user->id)->exists() || 
           ($record->institution_id && $record->institution_id === $user->institution_id);
}

// Enrollment access check for teachers
if ($user->hasRole('school_teacher')) {
    // Teachers can access enrollments for courses they teach
    return $record->course->teachers()->where('teacher_id', $user->id)->exists() ||
           ($record->course->institution_id && $record->course->institution_id === $user->institution_id);
}
```

## Testing

### Comprehensive Test Suite

**File**: `tests/Feature/CourseTeacherRelationshipTest.php`

The test suite covers:

1. **Multiple Teachers per Course**: Verifies courses can have multiple teachers
2. **Multiple Courses per Teacher**: Verifies teachers can have multiple courses
3. **Role Filtering**: Ensures only users with 'school_teacher' role are returned
4. **Pivot Data Storage**: Tests assignment timestamp storage
5. **Detachment**: Verifies teachers can be removed from courses
6. **Unique Constraints**: Tests duplicate assignment prevention
7. **Alias Methods**: Verifies `teachingCourses()` alias works
8. **Pivot Data Access**: Tests access to pivot table data
9. **Method Existence**: Confirms all relationship methods exist

### Running Tests

```bash
# Run all relationship tests
php artisan test tests/Feature/CourseTeacherRelationshipTest.php

# Run specific test
php artisan test --filter="course_can_have_multiple_teachers"
```

## Performance Considerations

### Database Indexes

The migration includes optimized indexes:
- `course_id` index for fast course-to-teachers lookups
- `teacher_id` index for fast teacher-to-courses lookups
- Unique composite index on `(course_id, teacher_id)` for constraint enforcement

### Query Optimization

```php
// Eager load relationships to avoid N+1 queries
$courses = Course::with('teachers')->get();
$teachers = User::with('courses')->whereHas('roles', function($q) {
    $q->where('name', 'school_teacher');
})->get();

// Use exists() for boolean checks instead of loading full relationships
$hasTeachers = $course->teachers()->exists();
$isAssigned = $course->teachers()->where('teacher_id', $teacherId)->exists();
```

## Error Handling

### Unique Constraint Violations

```php
try {
    $course->teachers()->attach($teacherId, ['assigned_at' => now()]);
} catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
    // Handle duplicate assignment attempt
    throw new \Exception('Teacher is already assigned to this course.');
}
```

### Role Validation

```php
// Ensure user has teacher role before assignment
if (!$user->hasRole('school_teacher')) {
    throw new \Exception('Only users with school_teacher role can be assigned to courses.');
}
```

## Migration Commands

```bash
# Run the migration
php artisan migrate

# Rollback if needed
php artisan migrate:rollback --step=1

# Check migration status
php artisan migrate:status
```

## Future Enhancements

### Potential Improvements

1. **Assignment Roles**: Add different types of teacher assignments (primary, assistant, etc.)
2. **Assignment Periods**: Add start/end dates for temporary assignments
3. **Assignment Approval**: Add workflow for assignment approval
4. **Notification System**: Notify teachers when assigned to new courses
5. **Bulk Operations**: Add methods for bulk teacher assignments

### Example Enhancement Migration

```php
// Future enhancement: Add assignment type
Schema::table('course_teachers', function (Blueprint $table) {
    $table->enum('assignment_type', ['primary', 'assistant', 'substitute'])
          ->default('primary')
          ->after('teacher_id');
    $table->date('start_date')->nullable()->after('assigned_at');
    $table->date('end_date')->nullable()->after('start_date');
});
```

## Troubleshooting

### Common Issues

1. **"Call to undefined method teachers()"**
   - Ensure the Course model includes the `teachers()` method
   - Check that the relationship is properly defined

2. **"Unique constraint violation"**
   - Check if teacher is already assigned to the course
   - Use `sync()` instead of `attach()` for updates

3. **"No teachers returned"**
   - Verify users have the 'school_teacher' role
   - Check that the role filtering query is correct

4. **"Pivot data not accessible"**
   - Ensure `withPivot()` is called in the relationship definition
   - Use `->pivot->field_name` to access pivot data

### Debug Commands

```php
// Check if relationship exists
$course = Course::find(1);
dd(method_exists($course, 'teachers'));

// Debug relationship query
$course->teachers()->toSql();

// Check pivot data
$course->teachers()->first()->pivot;
```

## Conclusion

The Course-Teacher relationship implementation provides a robust, scalable solution for managing teacher assignments in the learning center system. It follows Laravel's Eloquent conventions, includes proper type hints, comprehensive testing, and integrates seamlessly with the existing Shield-based permission system.

The implementation ensures data integrity through database constraints, provides optimal performance through proper indexing, and offers flexibility for future enhancements while maintaining backward compatibility.