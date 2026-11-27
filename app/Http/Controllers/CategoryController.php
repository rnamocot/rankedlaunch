<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    public function show(Category $category)
    {
        // Pro launches pinned on top, newest first.
        $featured = $category->products()->featured()->orderByDesc('launch_date')->orderBy('id')->get();

        $products = $category->products()->live()
            ->whereNotIn('id', $featured->pluck('id'))
            ->ranked()
            ->paginate(30);

        $categories = Category::where('group', $category->group)->orderBy('sort')->get();

        return view('category', compact('category', 'featured', 'products', 'categories'));
    }
}
