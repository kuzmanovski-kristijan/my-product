<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug', 'sort_order']);

        // Build tree
        $byParent = $categories->groupBy('parent_id');

        $build = function ($parentId) use (&$build, $byParent) {
            return ($byParent[$parentId] ?? collect())->map(function ($cat) use (&$build) {
                return [
                    'id' => $cat->id,
                    'parent_id' => $cat->parent_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'sort_order' => $cat->sort_order,
                    'children' => $build($cat->id)->values(),
                ];
            })->values();
        };

        return response()->json([
            'data' => $build(null),
        ]);
    }
}
