<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Instant search behind the Ctrl+K palette: apps, categories and makers. */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:80']]);
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['apps' => [], 'categories' => [], 'makers' => []]);
        }

        $like = '%'.addcslashes($q, '%_\\').'%';

        $apps = Product::live()->with('category')
            ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('tagline', 'like', $like))
            // Name matches first, then the most upvoted.
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$like])
            ->ranked()
            ->limit(6)
            ->get()
            ->map(fn (Product $p) => [
                'name' => $p->name,
                'sub' => $p->tagline,
                'image' => $p->logoUrl(),
                'url' => route('product', $p),
                'votes' => $p->votes_count,
            ]);

        $categories = Category::where('name', 'like', $like)->orderBy('name')->limit(4)->get()
            ->map(fn (Category $c) => ['name' => $c->name, 'url' => route('category', $c)]);

        $makers = User::whereNull('banned_at')->has('products')
            ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('username', 'like', $like))
            ->limit(4)->get()
            ->map(fn (User $u) => [
                'name' => $u->name,
                'sub' => '@'.$u->username,
                'image' => $u->avatar,
                'initials' => $u->initials(),
                'url' => $u->profileUrl(),
            ]);

        return response()->json(['apps' => $apps, 'categories' => $categories, 'makers' => $makers]);
    }
}
