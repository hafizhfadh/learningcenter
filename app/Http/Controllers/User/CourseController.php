<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index($exam)
    {
        $category = Category::where('slug', $exam)->firstOrFail();

        $courses = Course::where('category_id', $category->id)->latest()->when(request()->q, function($courses) {
            $courses = $courses->where('title', 'like', '%'. request()->q . '%');
        })->paginate(6);

        return view('user.course.index', compact('courses', 'exam'));
    }
}
