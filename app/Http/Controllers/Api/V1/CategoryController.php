<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Categories
 * 
 * APIs for browsing categories
 */
class CategoryController extends Controller
{
    /**
     * List all categories
     * 
     * Get list of categories with optional filtering.
     * 
     * @queryParam type string Filter by type (course, book, workshop). Example: course
     * @queryParam parent_id integer Get children of specific parent. Example: 1
     * @queryParam with_counts boolean Include item counts. Example: true
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "القيادة والإدارة",
     *       "slug": "leadership-management",
     *       "type": "course",
     *       "courses_count": 12,
     *       "books_count": 5,
     *       "has_children": true
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()
            ->active()
            ->orderBy('order');

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === '0') {
                $query->root();
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        } else {
            // Default: only root categories
            $query->root();
        }

        // Include counts
        if ($request->boolean('with_counts')) {
            $query->withCounts();
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Get category tree
     * 
     * Get hierarchical category tree.
     * 
     * @queryParam type string Filter by type. Example: course
     * @queryParam max_depth integer Maximum depth level. Example: 2
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "القيادة والإدارة",
     *       "children": [
     *         {
     *           "id": 2,
     *           "name": "القيادة التحويلية"
     *         }
     *       ]
     *     }
     *   ]
     * }
     */
    public function tree(Request $request): JsonResponse
    {
        
        $query = Category::query()
            ->active()
            ->root()
            ->with(['allChildren' => function($q) {
                $q->orderBy('order');
            }])
            ->orderBy('order');

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Get category details
     * 
     * Get details of a specific category.
     * 
     * @urlParam slug string required Category slug. Example: leadership-management
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "القيادة والإدارة",
     *     "description": "...",
     *     "breadcrumb": [...]
     *   }
     * }
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->active()
            ->withCount(['courses', 'books'])
            ->with(['parent', 'activeChildren'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Get category items
     * 
     * Get all items (courses and books) in a category.
     * 
     * @urlParam slug string required Category slug. Example: leadership-management
     * @queryParam type string Filter by type (courses, books). Example: courses
     * @queryParam page integer Page number. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "courses": [...],
     *     "books": [...]
     *   }
     * }
     */
    public function items(string $slug, Request $request): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->active()
            ->firstOrFail();

        // Get descendant category IDs (including this one)
        $categoryIds = $category->getDescendantIds();

        $result = [];

        // Get courses if requested
        if (!$request->has('type') || $request->type === 'courses') {
            $courses = \App\Models\Course::whereIn('category_id', $categoryIds)
                ->published()
                ->with(['instructor.user', 'category'])
                ->paginate(12);

            $result['courses'] = \App\Http\Resources\V1\CourseResource::collection($courses);
            $result['courses_meta'] = [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'total' => $courses->total(),
            ];
        }

        // Get books if requested
        if (!$request->has('type') || $request->type === 'books') {
            $books = \App\Models\Book::whereIn('category_id', $categoryIds)
                ->published()
                ->with(['author.user', 'category'])
                ->paginate(12);

            $result['books'] = \App\Http\Resources\V1\BookResource::collection($books);
            $result['books_meta'] = [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'total' => $books->total(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
