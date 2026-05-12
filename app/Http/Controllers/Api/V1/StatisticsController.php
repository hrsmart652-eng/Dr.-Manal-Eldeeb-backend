<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

use Illuminate\Http\Request;

/**
 * @group Statistics
 * 
 * Platform statistics and analytics
 */
class StatisticsController extends Controller
{
    /**
     * Get platform statistics
     * 
     * Get overall platform statistics.
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "students": {
     *       "total": 12000,
     *       "active": 5000,
     *       "new_this_month": 450
     *     },
     *     "courses": {
     *       "total": 150,
     *       "published": 120,
     *       "total_enrollments": 25000
     *     },
     *     "instructors": {
     *       "total": 25,
     *       "active": 20,
     *       "average_rating": 4.7
     *     }
     *   }
     * }
     */
    public function index(): JsonResponse
    {
        // Cache for 1 hour
        $stats = Cache::remember('platform_statistics', 3600, function () {
            return [
                'students' => [
                    'total' => User::where('type', 'student')->count(),
                    'active' => User::where('type', 'student')
                        ->where('is_active', true)
                        ->count(),
                    'new_this_month' => User::where('type', 'student')
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count(),
                ],
                'courses' => [
                    'total' => Course::count(),
                    'published' => Course::where('status', 'published')->count(),
                    'total_enrollments' => Enrollment::where('status', 'active')->count(),
                    'average_rating' => round(Course::avg('rating'), 2),
                ],
                'books' => [
                    'total' => Book::count(),
                    'published' => Book::where('status', 'published')->count(),
                    'total_sales' => Book::sum('total_sales'),
                ],
                'instructors' => [
                    'total' => Instructor::count(),
                    'featured' => Instructor::where('is_featured', true)->count(),
                    'average_rating' => round(Instructor::avg('rating'), 2),
                    'available_for_consultation' => Instructor::where('available_for_consultation', true)->count(),
                ],
                'bookings' => [
                    'total' => Booking::count(),
                    'pending' => Booking::where('status', 'pending')->count(),
                    'confirmed' => Booking::where('status', 'confirmed')->count(),
                    'completed' => Booking::where('status', 'completed')->count(),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get popular courses
     * 
     * Get list of most popular courses.
     * 
     * @queryParam limit integer Number of courses (max 10). Example: 5
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "دورة القيادة التحويلية",
     *       "enrolled_students": 1200,
     *       "rating": 4.8
     *     }
     *   ]
     * }
     */
    public function popularCourses(Request $request): JsonResponse
    {
        $limit = min($request->input('limit', 5), 10);

        $courses = Course::published()
            ->with(['instructor.user', 'category'])
            ->orderBy('is_featured', 'desc')
            ->orderBy('enrolled_students', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => \App\Http\Resources\V1\CourseResource::collection($courses),
        ]);
    }

    /**
     * Get top instructors
     * 
     * Get list of top-rated instructors.
     * 
     * @queryParam limit integer Number of instructors (max 10). Example: 5
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function topInstructors(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 5), 10);

        $instructors = Instructor::with('user')
            ->orderBy('rating', 'desc')
            ->orderBy('total_students', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => \App\Http\Resources\V1\InstructorResource::collection($instructors),
        ]);
    }

    /**
     * Get recent enrollments
     * 
     * Get recent course enrollments (public count only).
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "today": 45,
     *     "this_week": 234,
     *     "this_month": 892
     *   }
     * }
     */
    public function recentEnrollments(): JsonResponse
    {
        $stats = [
            'today' => Enrollment::whereDate('created_at', today())->count(),
            'this_week' => Enrollment::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month' => Enrollment::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
    public function landingPageCounters(): JsonResponse
{
    $stats = Cache::remember('landing_counters', 3600, function () {
     return [
    //the number of students
    'total_students' => User::where('type', 'student')->count(),
    
    // the number of daily visitors
    'daily_visitors' => 2000, 
    
    // the number of books/collections - based on the image showing "number of collections"
    'total_books' => Book::count(), 
    
    // the number of graduates - based on the image showing "number of graduates
    'graduates_count' => Enrollment::where('status', 'completed')->distinct('user_id')->count(),
];
    });

    return response()->json([
        'success' => true,
        'data' => $stats
    ]);
}
}
