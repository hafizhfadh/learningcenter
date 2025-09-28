<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index($exam)
    {
        $learningPath = LearningPath::where('slug', $exam)->firstOrFail();

        $courses = $learningPath->courses()
            ->when(request()->q, function($query) {
                $query->where('title', 'like', '%'. request()->q . '%');
            })
            ->latest()
            ->paginate(6);

        return view('user.course.index', compact('courses', 'exam'));
    }
}
