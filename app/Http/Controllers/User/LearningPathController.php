<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;

class LearningPathController extends Controller
{

    public function index()
    {
        $learningPaths = LearningPath::where('is_active', true)->latest()->when(request()->q, function($learningPaths) {
            $learningPaths = $learningPaths->where('name', 'like', '%'. request()->q . '%');
        })->paginate(6);

        return view('user.learning-path.index', compact('learningPaths'));
    }
}
