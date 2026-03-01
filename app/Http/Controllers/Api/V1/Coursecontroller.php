<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CourseResource;
use App\Http\Resources\V1\CourseDetailResource;
use App\Http\Resources\V1\ReviewResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Courses
 * 
 * APIs for browsing and viewing courses
 */
class CourseController extends Controller
{
    /**
     * List all courses
     * 
     * Get paginated list of published courses with filtering and sorting options.
     * 
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 50). Example: 12
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam instructor_id integer Filter by instructor ID. Example: 2
     * @queryParam level string Filter by level (beginner, intermediate, advanced). Example: beginner
     * @queryParam price string Filter by price (free, paid). Example: free
     * @queryParam search string Search in title and description. Example: القيادة
     * @queryParam sort string Sort by (popular, newest, rating, price_low, price_high). Example: popular
     * @queryParam featured_first boolean Show featured courses first. Example: true
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "courses": [
     *       {
     *         "id": 1,
     *         "title": "دورة القيادة التحويلية",
     *         "slug": "transformational-leadership",
     *         "description": "برنامج متقدم للقيادة",
     *         "thumbnail": "https://example.com/storage/courses/1.jpg",
     *         "price": 3500,
     *         "discount_price": null,
     *         "final_price": 3500,
     *         "level": "advanced",
     *         "duration_hours": 40,
     *         "total_lectures": 25,
     *         "enrolled_students": 1200,
     *         "rating": 4.8,
     *         "instructor": {
     *           "id": 1,
     *           "name": "د. منال الديب",
     *           "avatar": "..."
     *         }
     *       }
     *     ],
     *     "meta": {
     *       "current_page": 1,
     *       "last_page": 5,
     *       "per_page": 12,
     *       "total": 58
     *     }
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Course::query()
            ->with(['instructor.user', 'category'])
            ->published();

        // Apply filters
        $this->applyFilters($query, $request);

        // Apply sorting
        $this->applySorting($query, $request);

        // Featured first
        if ($request->boolean('featured_first')) {
            $query->orderBy('is_featured', 'desc');
        }

        // Pagination
        $perPage = min($request->get('per_page', 12), 50);
        $courses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'courses' => CourseResource::collection($courses),
                'meta' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'total' => $courses->total(),
                ],
                'links' => [
                    'first' => $courses->url(1),
                    'last' => $courses->url($courses->lastPage()),
                    'prev' => $courses->previousPageUrl(),
                    'next' => $courses->nextPageUrl(),
                ],
            ],
        ]);
    }

    /**
     * Get course details
     * 
     * Get full details of a specific course including sections, lectures, and reviews.
     * 
     * @urlParam slug string required Course slug. Example: transformational-leadership
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "title": "دورة القيادة التحويلية",
     *     "description": "برنامج احترافي متقدم...",
     *     "price": 3500,
     *     "final_price": 3500,
     *     "rating": 4.8,
     *     "total_reviews": 340,
     *     "enrolled_students": 1200,
     *     "instructor": {
     *       "id": 1,
     *       "name": "د. منال الديب",
     *       "bio": "خبيرة في القيادة...",
     *       "rating": 4.9
     *     },
     *     "sections": [
     *       {
     *         "id": 1,
     *         "title": "مقدمة في القيادة التحويلية",
     *         "lectures": [
     *           {
     *             "id": 1,
     *             "title": "ما هي القيادة التحويلية؟",
     *             "type": "video",
     *             "duration_minutes": 15,
     *             "is_preview": true
     *           }
     *         ]
     *       }
     *     ],
     *     "recent_reviews": []
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Course not found"
     * }
     */
    public function show(string $slug): JsonResponse
    {
        $course = Course::with([
            'instructor.user',
            'category',
            'sections.lectures' => function ($query) {
                $query->where(function($q) {
                    $q->where('is_preview', true)
                      ->orWhereNull('available_at')
                      ->orWhere('available_at', '<=', now());
                });
            },
        ])
        ->where('slug', $slug)
        ->published()
        ->firstOrFail();

        // Get recent reviews
        $recentReviews = $course->approvedReviews()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => new CourseDetailResource($course),
        ]);
    }

    /**
     * Get course reviews
     * 
     * Get paginated reviews for a specific course.
     * 
     * @urlParam slug string required Course slug. Example: transformational-leadership
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Reviews per page. Example: 10
     * @queryParam rating integer Filter by rating (1-5). Example: 5
     * @queryParam sort string Sort by (recent, helpful, rating_high, rating_low). Example: recent
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "reviews": [
     *       {
     *         "id": 1,
     *         "user": {
     *           "name": "أحمد محمد",
     *           "avatar": "..."
     *         },
     *         "rating": 5,
     *         "comment": "دورة رائعة ومفيدة جداً",
     *         "created_at": "2024-01-15"
     *       }
     *     ],
     *     "meta": {
     *       "current_page": 1,
     *       "last_page": 10,
     *       "total": 95
     *     }
     *   }
     * }
     */
    public function reviews(string $slug, Request $request): JsonResponse
    {
        $course = Course::where('slug', $slug)->firstOrFail();
        
        $query = $course->approvedReviews()->with('user');

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // Sorting
        $sort = $request->get('sort', 'recent');
        match ($sort) {
            'helpful' => $query->orderBy('helpful_count', 'desc'),
            'rating_high' => $query->orderBy('rating', 'desc'),
            'rating_low' => $query->orderBy('rating', 'asc'),
            default => $query->latest(),
        };

        $reviews = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => ReviewResource::collection($reviews),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'total' => $reviews->total(),
                ],
            ],
        ]);
    }

    /**
     * Get related courses
     * 
     * Get courses related to the specified course (same category).
     * 
     * @urlParam slug string required Course slug. Example: transformational-leadership
     * @queryParam limit integer Number of courses to return (max 10). Example: 4
     * 
     * @response 200 {
     *   "success": true,
     *   "data": []
     * }
     */
    public function related(string $slug): JsonResponse
    {
        $course = Course::where('slug', $slug)->firstOrFail();
        
        $limit = request()->get('limit', 4);
        $limit = min($limit, 10); // Max 10
        
        $relatedCourses = Course::where('category_id', $course->category_id)
            ->where('id', '!=', $course->id)
            ->published()
            ->with(['instructor.user', 'category'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($relatedCourses),
        ]);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request)
    {
        // Filter by category
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter by instructor
        if ($request->has('instructor_id')) {
            $query->byInstructor($request->instructor_id);
        }

        // Filter by level
        if ($request->has('level')) {
            $query->byLevel($request->level);
        }

        // Filter by price
        if ($request->has('price')) {
            if ($request->price === 'free') {
                $query->free();
            } elseif ($request->price === 'paid') {
                $query->paid();
            }
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by tags
        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $query->where(function($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, Request $request)
    {
        $sort = $request->get('sort', 'newest');
        
        match ($sort) {
            'popular' => $query->popular(),
            'rating' => $query->topRated(),
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'newest' => $query->recent(),
            default => $query->recent(),
        };
    }
}
