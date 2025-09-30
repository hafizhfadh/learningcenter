<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;

class CourseController extends Controller
{
    public function index($exam)
    {
        $learningPath = LearningPath::where('slug', $exam)->firstOrFail();

        $courses = $learningPath->courses()
            ->when(request('q'), function ($query, $q) {
                $query->where('title', 'like', '%' . $q . '%');
            })
            ->where('is_published', true)
            ->latest()
            ->paginate(6);

        return view('user.course.index', compact('courses', 'exam'));
    }
}
