<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{

    public function index()
    {
        $categories = Category::where('is_active', true)->latest()->when(request()->q, function($categories) {
            $categories = $categories->where('name', 'like', '%'. request()->q . '%');
        })->paginate(6);

        return view('user.category.index', compact('categories'));
    }
}
