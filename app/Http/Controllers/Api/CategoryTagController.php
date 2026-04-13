<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class CategoryTagController extends Controller
{
    /** GET /categories — public list of active categories */
    public function categories(): JsonResponse
    {
        $categories = Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'emoji', 'color', 'description']);

        return response()->json($categories);
    }

    /** GET /tags — public list of active tags */
    public function tags(): JsonResponse
    {
        $tags = Tag::active()
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return response()->json($tags);
    }
}
