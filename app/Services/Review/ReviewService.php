<?php

namespace App\Services\Review;

use App\Models\Review;
use App\Models\Course;
use App\Models\Book;

class ReviewService
{
    /**
     * Create a new review
     */
    public function createReview(array $data): Review
    {
        $review = Review::create($data);

        // Update reviewable rating
        $this->updateReviewableRating($review);

        return $review;
    }

    /**
     * Update review
     */
    public function updateReview(Review $review, array $data): Review
    {
        $review->update($data);

        // Update reviewable rating
        $this->updateReviewableRating($review);

        return $review->fresh();
    }

    /**
     * Delete review
     */
    public function deleteReview(Review $review): void
    {
        $reviewableType = $review->reviewable_type;
        $reviewableId = $review->reviewable_id;

        $review->delete();

        // Update reviewable rating
        if ($reviewableType === Course::class) {
            $course = Course::find($reviewableId);
            $course?->updateRating();
        } elseif ($reviewableType === Book::class) {
            $book = Book::find($reviewableId);
            $book?->updateRating();
        }
    }

    /**
     * Approve review
     */
    public function approveReview(Review $review, int $approvedBy): Review
    {
        $review->update([
            'is_approved' => true,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        // Update reviewable rating
        $this->updateReviewableRating($review);

        return $review->fresh();
    }

    /**
     * Update reviewable (course/book) rating
     */
    private function updateReviewableRating(Review $review): void
    {
        $reviewable = $review->reviewable;

        if ($reviewable instanceof Course || $reviewable instanceof Book) {
            $reviewable->updateRating();
        }
    }

    /**
     * Mark review as helpful
     */
    public function markHelpful(Review $review, int $userId): void
    {
        // Logic to track helpful votes
        $review->increment('helpful_count');
    }

    /**
     * Mark review as not helpful
     */
    public function markNotHelpful(Review $review, int $userId): void
    {
        // Logic to track not helpful votes
        $review->increment('not_helpful_count');
    }
}