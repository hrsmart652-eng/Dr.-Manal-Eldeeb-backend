<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EnrollmentResource;
use App\Http\Resources\V1\CourseProgressResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\LectureProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Student - Courses
 * 
 * APIs for student enrolled courses
 */
class CourseController extends Controller
{
    /**
     * Get my enrolled courses
     * 
     * Get list of courses the authenticated student is enrolled in.
     * 
     * @authenticated
     * 
     * @queryParam status string Filter by status (active, completed, expired). Example: active
     * @queryParam page integer Page number. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "enrollment_id": 1,
     *       "course": {
     *         "id": 1,
     *         "title": "دورة القيادة التحويلية",
     *         "thumbnail": "...",
     *         "instructor": "د. منال الديب"
     *       },
     *       "progress_percentage": 45,
     *       "status": "active",
     *       "enrolled_at": "2024-01-10",
     *       "last_accessed": "2024-02-01"
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->enrollments()
            ->with(['course.instructor.user', 'course.category'])
            ->where('payment_status', 'completed');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->latest()->paginate(12);

        return response()->json([
            'success' => true,
            'data' => EnrollmentResource::collection($enrollments),
            'meta' => [
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
                'total' => $enrollments->total(),
            ],
        ]);
    }

    /**
     * Enroll in a course
     * 
     * Enroll the authenticated student in a course.
     * 
     * @authenticated
     * 
     * @urlParam courseId integer required Course ID. Example: 1
     * @bodyParam payment_method string Payment method (will integrate payment gateway). Example: free
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "تم التسجيل في الدورة بنجاح",
     *   "data": {
     *     "enrollment_id": 1,
     *     "course": {},
     *     "status": "active"
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "أنت مسجل بالفعل في هذه الدورة"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "الدورة ممتلئة"
     * }
     */
    public function enroll(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);

        // Check if already enrolled
        if ($course->isEnrolledBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'أنت مسجل بالفعل في هذه الدورة',
            ], 400);
        }

        // Check if course can be enrolled
        if (!$course->canEnroll()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن التسجيل في هذه الدورة حالياً',
            ], 422);
        }

        // Check if course is full
        if ($course->is_full) {
            return response()->json([
                'success' => false,
                'message' => 'الدورة ممتلئة',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // For free courses or when payment is completed
            $paymentStatus = $course->price == 0 ? 'completed' : 'pending';

            // Create enrollment
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'price_paid' => $course->final_price,
                'payment_method' => $request->payment_method ?? 'free',
                'payment_status' => $paymentStatus,
                'status' => $paymentStatus === 'completed' ? 'active' : 'pending',
            ]);

            // If free course, increment enrollment count
            if ($paymentStatus === 'completed') {
                $course->incrementEnrollments();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $paymentStatus === 'completed' 
                    ? 'تم التسجيل في الدورة بنجاح' 
                    : 'يرجى إتمام عملية الدفع',
                'data' => new EnrollmentResource($enrollment->load('course.instructor.user')),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.',
            ], 500);
        }
    }

    /**
     * Get enrolled course details
     * 
     * Get full course details for an enrolled student including locked lectures.
     * 
     * @authenticated
     * 
     * @urlParam courseId integer required Course ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "course": {},
     *     "enrollment": {
     *       "progress_percentage": 45,
     *       "completed_lectures": 12,
     *       "total_lectures": 25
     *     }
     *   }
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "غير مسجل في هذه الدورة"
     * }
     */
    public function show(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        $course = Course::with([
            'instructor.user',
            'category',
            'sections.lectures',
        ])->findOrFail($courseId);

        // Check enrollment
        $enrollment = $course->enrollmentFor($user);
        
        if (!$enrollment || !$enrollment->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسجل في هذه الدورة',
            ], 403);
        }

        // Touch last accessed
        $enrollment->touchLastAccessed();

        // Get lecture progress
        $lectureProgress = $enrollment->lectureProgress()
            ->pluck('is_completed', 'lecture_id');

        return response()->json([
            'success' => true,
            'data' => [
                'course' => new \App\Http\Resources\V1\CourseDetailResource($course),
                'enrollment' => [
                    'id' => $enrollment->id,
                    'progress_percentage' => $enrollment->progress_percentage,
                    'completed_lectures' => $enrollment->completed_lectures,
                    'total_lectures' => $course->total_lectures,
                    'status' => $enrollment->status,
                    'enrolled_at' => $enrollment->created_at->toISOString(),
                ],
                'lecture_progress' => $lectureProgress,
            ],
        ]);
    }

    /**
     * Get course progress
     * 
     * Get detailed progress for a course.
     * 
     * @authenticated
     * 
     * @urlParam courseId integer required Course ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "progress_percentage": 45,
     *     "completed_lectures": 12,
     *     "total_lectures": 25,
     *     "time_spent_minutes": 540,
     *     "sections": []
     *   }
     * }
     */
    public function progress(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        $course = Course::with('sections.lectures')->findOrFail($courseId);

        $enrollment = $course->enrollmentFor($user);
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسجل في هذه الدورة',
            ], 403);
        }

        // Get all lecture progress
        $allProgress = $enrollment->lectureProgress()
            ->with('lecture')
            ->get()
            ->keyBy('lecture_id');

        // Calculate total time spent
        $totalTimeSpent = $allProgress->sum('watch_time_seconds') / 60; // Convert to minutes

        // Build progress by section
        $sectionsProgress = $course->sections->map(function ($section) use ($allProgress) {
            $lectures = $section->lectures->map(function ($lecture) use ($allProgress) {
                $progress = $allProgress->get($lecture->id);
                
                return [
                    'lecture_id' => $lecture->id,
                    'title' => $lecture->title_ar,
                    'duration_minutes' => $lecture->duration_minutes,
                    'is_completed' => $progress ? $progress->is_completed : false,
                    'completion_percentage' => $progress ? $progress->completion_percentage : 0,
                    'watch_time_seconds' => $progress ? $progress->watch_time_seconds : 0,
                ];
            });

            $completedCount = $lectures->where('is_completed', true)->count();
            $totalCount = $lectures->count();

            return [
                'section_id' => $section->id,
                'title' => $section->title_ar,
                'lectures' => $lectures,
                'completed_lectures' => $completedCount,
                'total_lectures' => $totalCount,
                'progress_percentage' => $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'progress_percentage' => $enrollment->progress_percentage,
                'completed_lectures' => $enrollment->completed_lectures,
                'total_lectures' => $course->total_lectures,
                'time_spent_minutes' => round($totalTimeSpent),
                'status' => $enrollment->status,
                'sections' => $sectionsProgress,
            ],
        ]);
    }

    /**
     * Mark lecture as complete
     * 
     * Mark a specific lecture as completed and update course progress.
     * 
     * @authenticated
     * 
     * @urlParam courseId integer required Course ID. Example: 1
     * @urlParam lectureId integer required Lecture ID. Example: 5
     * @bodyParam watch_time_seconds integer Watch time in seconds. Example: 450
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "تم تحديد المحاضرة كمكتملة",
     *   "data": {
     *     "progress_percentage": 48,
     *     "completed_lectures": 13
     *   }
     * }
     */
    public function markLectureComplete(Request $request, int $courseId, int $lectureId): JsonResponse
    {
        $request->validate([
            'watch_time_seconds' => 'sometimes|integer|min:0',
        ]);

        $user = $request->user();
        $lecture = Lecture::findOrFail($lectureId);
        $course = Course::findOrFail($courseId);

        // Verify enrollment
        $enrollment = $course->enrollmentFor($user);
        
        if (!$enrollment || !$enrollment->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسجل في هذه الدورة',
            ], 403);
        }

        // Create or update lecture progress
        $progress = LectureProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'lecture_id' => $lectureId,
                'enrollment_id' => $enrollment->id,
            ],
            [
                'total_duration_seconds' => $lecture->duration_minutes * 60,
                'watch_time_seconds' => $request->get('watch_time_seconds', $lecture->duration_minutes * 60),
            ]
        );

        // Mark as completed
        if (!$progress->is_completed) {
            $progress->markCompleted();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد المحاضرة كمكتملة',
            'data' => [
                'progress_percentage' => $enrollment->fresh()->progress_percentage,
                'completed_lectures' => $enrollment->fresh()->completed_lectures,
                'is_course_completed' => $enrollment->fresh()->isCompleted(),
            ],
        ]);
    }
}
