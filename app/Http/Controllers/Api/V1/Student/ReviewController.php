<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReviewRequest;
use App\Http\Resources\V1\ReviewResource;
use App\Models\Course;
use App\Models\Book;
use App\Models\Review;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Student - Reviews
 * 
 * APIs for submitting and managing reviews
 */
class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService
    ) {}

    /**
     * Submit course review
     * 
     * Submit a review for a purchased/enrolled course.
     * 
     * @authenticated
     * 
     * @urlParam courseId integer required Course ID. Example: 1
     * @bodyParam rating integer required Rating (1-5). Example: 5
     * @bodyParam comment text required Review comment. Example: دورة ممتازة واستفدت منها كثيراً
     * @bodyParam pros array Optional positive points. Example: ["محتوى غني", "شرح واضح"]
     * @bodyParam cons array Optional negative points. Example: ["بعض الأجزاء طويلة"]
     */
    public function storeCourseReview(ReviewRequest $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);

        // Check if enrolled
        if (!$course->isEnrolledBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'يجب التسجيل في الدورة أولاً لإضافة تقييم',
            ], 403);
        }

        // Check if already reviewed
        $existingReview = $course->reviews()->where('user_id', $user->id)->first();
        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'لقد قمت بتقييم هذه الدورة بالفعل',
            ], 400);
        }

        // Create review
        $review = $this->reviewService->createReview([
            'user_id' => $user->id,
            'reviewable_type' => Course::class,
            'reviewable_id' => $course->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'pros' => $request->pros,
            'cons' => $request->cons,
            'is_verified_purchase' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التقييم بنجاح. سيتم عرضه بعد الموافقة عليه.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Update course review
     * 
     * Update an existing course review.
     * 
     * @authenticated
     * 
     * @urlParam courseId integer required Course ID. Example: 1
     * @bodyParam rating integer Rating (1-5). Example: 4
     * @bodyParam comment text Review comment. Example: دورة جيدة
     */
    public function updateCourseReview(ReviewRequest $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);

        $review = $course->reviews()->where('user_id', $user->id)->firstOrFail();

        $review->update([
            'rating' => $request->rating ?? $review->rating,
            'comment' => $request->comment ?? $review->comment,
            'pros' => $request->pros ?? $review->pros,
            'cons' => $request->cons ?? $review->cons,
            'is_approved' => false, // Reset approval
        ]);

        // Update course rating
        $course->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التقييم بنجاح',
            'data' => new ReviewResource($review->fresh()),
        ]);
    }

    /**
     * Delete course review
     * 
     * Delete a course review.
     * 
     * @authenticated
     * 
     * @urlParam courseId integer required Course ID. Example: 1
     */
    public function deleteCourseReview(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);

        $review = $course->reviews()->where('user_id', $user->id)->firstOrFail();
        $review->delete();

        // Update course rating
        $course->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التقييم بنجاح',
        ]);
    }

    /**
     * Submit book review
     * 
     * Submit a review for a purchased book.
     * 
     * @authenticated
     * 
     * @urlParam bookId integer required Book ID. Example: 1
     * @bodyParam rating integer required Rating (1-5). Example: 5
     * @bodyParam comment text required Review comment. Example: كتاب رائع ومفيد
     */
    public function storeBookReview(ReviewRequest $request, int $bookId): JsonResponse
    {
        $user = $request->user();
        $book = Book::findOrFail($bookId);

        // Check if purchased
        if (!$book->isPurchasedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'يجب شراء الكتاب أولاً لإضافة تقييم',
            ], 403);
        }

        // Check if already reviewed
        $existingReview = $book->reviews()->where('user_id', $user->id)->first();
        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'لقد قمت بتقييم هذا الكتاب بالفعل',
            ], 400);
        }

        // Create review
        $review = $this->reviewService->createReview([
            'user_id' => $user->id,
            'reviewable_type' => Book::class,
            'reviewable_id' => $book->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'pros' => $request->pros,
            'cons' => $request->cons,
            'is_verified_purchase' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التقييم بنجاح. سيتم عرضه بعد الموافقة عليه.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Update book review
     * 
     * Update an existing book review.
     * 
     * @authenticated
     * 
     * @urlParam bookId integer required Book ID. Example: 1
     * @bodyParam rating integer Rating (1-5). Example: 4
     * @bodyParam comment text Review comment. Example: كتاب جيد
     */
    public function updateBookReview(ReviewRequest $request, int $bookId): JsonResponse
    {
        $user = $request->user();
        $book = Book::findOrFail($bookId);

        $review = $book->reviews()->where('user_id', $user->id)->firstOrFail();

        $review->update([
            'rating' => $request->rating ?? $review->rating,
            'comment' => $request->comment ?? $review->comment,
            'pros' => $request->pros ?? $review->pros,
            'cons' => $request->cons ?? $review->cons,
            'is_approved' => false, // Reset approval
        ]);

        // Update book rating
        $book->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التقييم بنجاح',
            'data' => new ReviewResource($review->fresh()),
        ]);
    }

    /**
     * Delete book review
     * 
     * Delete a book review.
     * 
     * @authenticated
     * 
     * @urlParam bookId integer required Book ID. Example: 1
     */
    public function deleteBookReview(Request $request, int $bookId): JsonResponse
    {
        $user = $request->user();
        $book = Book::findOrFail($bookId);

        $review = $book->reviews()->where('user_id', $user->id)->firstOrFail();
        $review->delete();

        // Update book rating
        $book->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التقييم بنجاح',
        ]);
    }
}