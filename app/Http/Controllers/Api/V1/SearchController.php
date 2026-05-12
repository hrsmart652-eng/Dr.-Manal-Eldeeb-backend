<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CourseResource;
use App\Http\Resources\V1\BookResource;
use App\Http\Resources\V1\InstructorResource;
use App\Models\Course;
use App\Models\Book;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Search
 * 
 * Global search across all content
 */
class SearchController extends Controller
{
    /**
     * Global search
     * 
     * Search across courses, books, and instructors.
     * 
     * @queryParam q string required Search query (min 2 chars). Example: القيادة
     * @queryParam type string Filter by type (courses, books, instructors, all). Example: all
     * @queryParam limit integer Results per type (max 20). Example: 10
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "query": "القيادة",
     *     "total_results": 45,
     *     "courses": {
     *       "count": 15,
     *       "results": [...]
     *     },
     *     "books": {
     *       "count": 20,
     *       "results": [...]
     *     },
     *     "instructors": {
     *       "count": 10,
     *       "results": [...]
     *     }
     *   }
     * }
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'sometimes|in:courses,books,instructors,all',
            'limit' => 'sometimes|integer|min:1|max:20',
        ], [
            'q.required' => 'يرجى إدخال كلمة البحث',
            'q.min' => 'يجب أن تكون كلمة البحث حرفين على الأقل',
        ]);

        $query = $request->q;
        $type = $request->get('type', 'all');
        $limit = min($request->get('limit', 10), 20);

        $results = [
            'query' => $query,
            'total_results' => 0,
        ];

        // Search courses
        if ($type === 'all' || $type === 'courses') {
            $courses = Course::published()
                ->with(['instructor.user', 'category'])
                ->search($query)
                ->limit($limit)
                ->get();

            $results['courses'] = [
                'count' => $courses->count(),
                'results' => CourseResource::collection($courses),
            ];
            $results['total_results'] += $courses->count();
        }

        // Search books
        if ($type === 'all' || $type === 'books') {
            $books = Book::published()
                ->with(['author.user', 'category'])
                ->search($query)
                ->limit($limit)
                ->get();

            $results['books'] = [
                'count' => $books->count(),
                'results' => BookResource::collection($books),
            ];
            $results['total_results'] += $books->count();
        }

        // Search instructors
        if ($type === 'all' || $type === 'instructors') {
            $instructors = Instructor::with('user')
                ->where(function($q) use ($query) {
                    $q->whereHas('user', function($uq) use ($query) {
                        $uq->where('name', 'like', "%{$query}%");
                    })
                    ->orWhere('specialization_ar', 'like', "%{$query}%")
                    ->orWhere('specialization_en', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get();

            $results['instructors'] = [
                'count' => $instructors->count(),
                'results' => InstructorResource::collection($instructors),
            ];
            $results['total_results'] += $instructors->count();
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Search suggestions
     * 
     * Get autocomplete suggestions for search.
     * 
     * @queryParam q string required Search query (min 2 chars). Example: قيا
     * @queryParam limit integer Max suggestions (default 5). Example: 5
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "suggestions": [
     *       "القيادة",
     *       "القيادة التحويلية",
     *       "قيادة الفريق"
     *     ]
     *   }
     * }
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:50',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        $query = $request->q;
        $limit = min($request->get('limit', 5), 10);

        $suggestions = collect();

        // Get course titles
        $courseTitles = Course::published()
            ->where('title_ar', 'like', "%{$query}%")
            ->pluck('title_ar')
            ->take($limit);

        $suggestions = $suggestions->merge($courseTitles);

        // Get book titles
        $bookTitles = Book::published()
            ->where('title_ar', 'like', "%{$query}%")
            ->pluck('title_ar')
            ->take($limit - $suggestions->count());

        $suggestions = $suggestions->merge($bookTitles);

        // Get instructor names
        if ($suggestions->count() < $limit) {
            $instructorNames = Instructor::whereHas('user', function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                })
                ->with('user')
                ->get()
                ->pluck('user.name')
                ->take($limit - $suggestions->count());

            $suggestions = $suggestions->merge($instructorNames);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'suggestions' => $suggestions->unique()->values()->take($limit),
            ],
        ]);
    }

    /**
     * Popular searches
     * 
     * Get list of popular/trending search terms.
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "popular_searches": [
     *       "القيادة التحويلية",
     *       "التسويق الرقمي",
     *       "التطوير الذاتي"
     *     ]
     *   }
     * }
     */
    public function popular(): JsonResponse
    {
        // In a real app, this would come from search analytics
        // For now, we'll return popular topics
        $popular = [
            'القيادة التحويلية',
            'التسويق الرقمي',
            'التطوير الذاتي',
            'إدارة المشاريع',
            'البرمجة اللغوية العصبية',
            'التخطيط الاستراتيجي',
            'إدارة الوقت',
            'مهارات التواصل',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'popular_searches' => $popular,
            ],
        ]);
    }
}