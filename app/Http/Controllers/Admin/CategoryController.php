<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories', [
            'categories' => Category::withCount('products')->orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40', 'unique:categories,name'],
            'group' => ['nullable', 'string', 'max:40'],
        ]);

        Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'group' => $data['group'] ?? null,
            'sort' => (int) Category::max('sort') + 1,
        ]);
        ActivityLog::record('settings', "Added category {$data['name']}", user: $request->user());

        return back()->with('status', 'Category added.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40', Rule::unique('categories')->ignore($category->id)],
            'slug' => ['required', 'alpha_dash', 'max:60', Rule::unique('categories')->ignore($category->id)],
            'sort' => ['required', 'integer', 'min:0'],
            'group' => ['nullable', 'string', 'max:40'],
        ]);

        $category->update($data);

        return back()->with('status', "{$category->name} saved.");
    }

    public function destroy(Request $request, Category $category)
    {
        abort_if($category->products()->exists(), 422, 'Move this category’s products first.');

        $category->delete();
        ActivityLog::record('settings', "Deleted category {$category->name}", user: $request->user());

        return back()->with('status', 'Category deleted.');
    }
}
