<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\InstructorResource;
use App\Http\Resources\V1\InstructorDetailResource;
use App\Http\Resources\V1\CourseResource;
use App\Http\Resources\V1\BookResource;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Instructors
 * 
 * APIs for browsing instructor profiles
 */
class InstructorController extends Controller
{
    /**
     * List all instructors
     * 
     * Get paginated list of instructors with filtering.
     * 
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 50). Example: 12
     * @queryParam specialization string Filter by specialization. Example: القيادة
     * @queryParam available_for_consultation boolean Filter available for consultation. Example: true
     * @queryParam sort string Sort by (rating, students, courses, books). Example: rating
     * @queryParam search string Search in name and specialization. Example: منال
     */
    public function index(Request $request): JsonResponse
    {
        $query = Instructor::query()->with('user');

        // Apply filters
        if ($request->has('specialization')) {
            $query->where(function($q) use ($request) {
                $q->where('specialization_ar', 'like', "%{$request->specialization}%")
                  ->orWhere('specialization_en', 'like', "%{$request->specialization}%");
            });
        }
        

        if ($request->has('available_for_consultation')) {
    $query->where(
        'available_for_consultation',
        $request->boolean('available_for_consultation')
    );
}


        if ($request->has('search')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            })->orWhere('specialization_ar', 'like', "%{$request->search}%");
        }

        // Apply sorting
        $sort = $request->get('sort', 'rating');
        match ($sort) {
            'students' => $query->orderBy('total_students', 'desc'),
            'courses' => $query->orderBy('total_courses', 'desc'),
            'books' => $query->orderBy('total_books', 'desc'),
            default => $query->orderBy('rating', 'desc'),
        };

        // Featured first
        $query->orderBy('is_featured', 'desc');

        $perPage = min($request->get('per_page', 12), 50);
        $instructors = $query->paginate($perPage);


        if (
    $request->has('available_for_consultation') &&
    $request->boolean('available_for_consultation') == true &&
    $instructors->isEmpty()
) {
    return response()->json([
        'success' => true,
        'data' => [],
        'message' => 'لا يوجد استشارات حالياً',
    ]);
}
        return response()->json([
            'success' => true,
            'data' => [
                'instructors' => InstructorResource::collection($instructors),
                'meta' => [
                    'current_page' => $instructors->currentPage(),
                    'last_page' => $instructors->lastPage(),
                    'per_page' => $instructors->perPage(),
                    'total' => $instructors->total(),
                ],
            ],
        ]);
    }

    /**
     * Get instructor details
     * 
     * Get full details of a specific instructor.
     * 
     * @urlParam id integer required Instructor ID. Example: 1
     */
    public function show(int $id): JsonResponse
    {
        $instructor = Instructor::with(['user', 'courses', 'books'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new InstructorDetailResource($instructor),
        ]);
    }

    /**
     * Get instructor courses
     * 
     * Get list of courses by instructor.
     * 
     * @urlParam id integer required Instructor ID. Example: 1
     * @queryParam status string Filter by status. Example: published
     */
    public function courses(int $id, Request $request): JsonResponse
    {
        $instructor = Instructor::findOrFail($id);
        $coursesQuery = $instructor->courses()->published();

if (!$coursesQuery->exists()) {
    return response()->json([
        'success' => true,
        'data' => [],
        'message' => 'لا يوجد كورسات حالياً',
    ], 200);
}
        $query = $instructor->courses()
            ->with(['category', 'instructor.user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->published();
        }

        $courses = $query->latest('published_at')->paginate(12);

        return response()->json([
            'success' => true,
            'data' => [
                'courses' => CourseResource::collection($courses),
                'meta' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'total' => $courses->total(),
                ],
            ],
        ]);
    }

    /**
     * Get instructor books
     * 
     * Get list of books by instructor.
     * 
     * @urlParam id integer required Instructor ID. Example: 1
     */
    public function books(int $id): JsonResponse
    {
        $instructor = Instructor::findOrFail($id);
          $booksQuery = $instructor->books()->published();

if (!$booksQuery->exists()) {
    return response()->json([
        'success' => true,
        'data' => [],
        'message' => 'لا يوجد كتب حالياً',
    ], 200);
}
        
        $books = $instructor->books()
            ->with(['category', 'author.user'])
            ->published()
            ->latest('published_at')
            ->paginate(12);

     
        return response()->json([
            'success' => true,
            'data' => [
                'books' => BookResource::collection($books),
                'meta' => [
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage(),
                    'total' => $books->total(),
                ],
            ],
        ]);

        
    }

    /**
     * Get instructor availability
     * 
     * Get available time slots for booking.
     * 
     * @urlParam id integer required Instructor ID. Example: 1
     * @queryParam date date required Date to check (Y-m-d). Example: 2024-03-15
     * @queryParam duration integer Consultation duration in minutes. Example: 60
     */
    public function availability(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'sometimes|integer|min:30|max:120',
        ]);

        $instructor = Instructor::findOrFail($id);

        if (!$instructor->available_for_consultation) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المحاضر غير متاح للاستشارات حالياً',
            ], 400);
        }

        $date = $request->date;
        $duration = $request->get('duration', 60);

        $availableSlots = $instructor->getAvailableSlots($date, $duration);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'instructor_id' => $instructor->id,
                'instructor_name' => $instructor->user->name,
                'consultation_price' => (float) $instructor->consultation_price,
                'duration_minutes' => $duration,
                'available_slots' => $availableSlots,
                'total_slots' => count($availableSlots),
            ],
        ]);
    }
}