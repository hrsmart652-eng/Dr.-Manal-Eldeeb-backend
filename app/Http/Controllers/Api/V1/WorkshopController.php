<?php



namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WorkshopResource;
use App\Http\Resources\V1\WorkshopDetailResource;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public - Workshops
 * 
 * APIs for browsing workshops
 */
class WorkshopController extends Controller
{
    /**
     * Get all workshops
     * 
     * Browse published workshops with filtering and pagination.
     * 
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 50). Example: 12
     * @queryParam type string Filter by type (online, in_person, hybrid). Example: online
     * @queryParam level string Filter by level (beginner, intermediate, advanced, all). Example: beginner
     * @queryParam category_id integer Filter by category. Example: 1
     * @queryParam instructor_id integer Filter by instructor. Example: 1
     * @queryParam search string Search in title and description. Example: قيادة
     * @queryParam status string Filter by status (upcoming, ongoing, completed). Example: upcoming
     * @queryParam sort string Sort by (newest, oldest, popular, price_low, price_high). Example: popular
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "ورشة عمل القيادة التحويلية",
     *       "slug": "transformational-leadership-workshop",
     *       "description": "ورشة عمل شاملة...",
     *       "instructor": {
     *         "id": 1,
     *         "name": "د. منال الديب"
     *       },
     *       "type": "online",
     *       "level": "intermediate",
     *       "price": 350,
     *       "final_price": 280,
     *       "start_date": "2024-05-15",
     *       "duration_hours": 12,
     *       "sessions_count": 4,
     *       "registered_count": 25,
     *       "available_spots": 25,
     *       "rating": 4.8
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 15
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Workshop::query()
            ->with(['instructor.user', 'category'])
            ->published();

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by level
        if ($request->has('level')) {
            $query->byLevel($request->level);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by instructor
        if ($request->has('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'ongoing':
                    $query->ongoing();
                    break;
                case 'completed':
                    $query->completed();
                    break;
            }
        }

        // Sorting
        switch ($request->get('sort', 'newest')) {
            case 'newest':
                $query->latest();
                break;
            case 'oldest':
                $query->oldest();
                break;
            case 'popular':
                $query->orderBy('registered_participants', 'desc');
                break;
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
        }

        $perPage = min($request->get('per_page', 12), 50);
        $workshops = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => WorkshopResource::collection($workshops),
            'meta' => [
                'current_page' => $workshops->currentPage(),
                'last_page' => $workshops->lastPage(),
                'per_page' => $workshops->perPage(),
                'total' => $workshops->total(),
            ],
        ]);
    }

    /**
     * Get workshop details
     * 
     * Get complete workshop information including sessions.
     * 
     * @urlParam slug string required Workshop slug. Example: transformational-leadership-workshop
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "title": "ورشة عمل القيادة التحويلية",
     *     "description": "ورشة عمل شاملة تهدف إلى...",
     *     "objectives": "سوف تتعلم...",
     *     "prerequisites": "معرفة أساسية...",
     *     "instructor": {
     *       "id": 1,
     *       "name": "د. منال الديب",
     *       "title": "دكتوراه",
     *       "specialization": "القيادة التحويلية"
     *     },
     *     "type": "online",
     *     "start_date": "2024-05-15",
     *     "end_date": "2024-05-18",
     *     "duration_hours": 12,
     *     "sessions": [
     *       {
     *         "id": 1,
     *         "title": "الجلسة الأولى: مقدمة في القيادة",
     *         "session_date": "2024-05-15",
     *         "start_time": "18:00",
     *         "end_time": "21:00"
     *       }
     *     ],
     *     "price": 350,
     *     "final_price": 280,
     *     "early_bird_deadline": "2024-05-01",
     *     "max_attendees": 50,
     *     "registered_count": 25,
     *     "available_spots": 25,
     *     "certificate_provided": true,
     *     "rating": 4.8,
     *     "can_register": true
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "الورشة غير موجودة"
     * }
     */
    public function show(string $slug): JsonResponse
    {

   
        $workshop = Workshop::where('slug', $slug)
            ->with([
                'instructor.user',
                'category',
                'sessions' => function($query) {
                    $query->orderBy('session_number');
                }
            ])
            ->first();

        if (!$workshop) {
            return response()->json([
        'message' => 'Stop! You are in the SHOW method',
        'received_slug' => $slug
    ]);
            // return response()->json([
            //     'success' => false,
            //     'message' => 'الورشة غير موجودة',
            // ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new WorkshopDetailResource($workshop),
        ]);
    }

    /**
     * Get upcoming workshops
     * 
     * Get list of upcoming workshops only.
     * 
     * @queryParam limit integer Number of workshops to return. Example: 6
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function upcoming(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 6), 20);

        $workshops = Workshop::with(['instructor.user', 'category'])
            ->upcoming()
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => WorkshopResource::collection($workshops),
            
        ]);
        
        
    }

    /**
     * Get featured workshops
     * 
     * Get featured/popular workshops.
     * 
     * @queryParam limit integer Number of workshops. Example: 4
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 4), 10);

        $workshops = Workshop::with(['instructor.user', 'category'])
            ->published()
            ->where('rating', '>=', 4.5)
            ->orderBy('rating', 'desc')
            ->orderBy('registered_participants', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => WorkshopResource::collection($workshops),
        ]);
    }

    /**
     * Get related workshops
     * 
     * Get workshops related to a specific workshop (same category/instructor).
     * 
     * @urlParam slug string required Workshop slug. Example: leadership-workshop
     * @queryParam limit integer Number of results. Example: 4
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function related(string $slug, Request $request): JsonResponse
    {
        $workshop = Workshop::where('slug', $slug)->firstOrFail();
        $limit = min($request->get('limit', 4), 10);

        $related = Workshop::with(['instructor.user', 'category'])
            ->published()
            ->where('id', '!=', $workshop->id)
            ->where(function($query) use ($workshop) {
                $query->where('category_id', $workshop->category_id)
                    ->orWhere('instructor_id', $workshop->instructor_id);
            })
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => WorkshopResource::collection($related),
        ]);
    }
}

