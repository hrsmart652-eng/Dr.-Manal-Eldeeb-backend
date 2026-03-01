<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reviews table
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->morphs('reviewable'); // reviewable_type + reviewable_id
                $table->unsignedTinyInteger('rating'); // 1-5
                $table->text('comment')->nullable();
                $table->json('pros')->nullable();
                $table->json('cons')->nullable();
                $table->boolean('is_approved')->default(false);
                $table->boolean('is_verified_purchase')->default(false);
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->integer('helpful_count')->default(0);
                $table->integer('not_helpful_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index(['reviewable_type', 'reviewable_id', 'is_approved'], 'reviews_reviewable_idx');
                $table->index('rating', 'reviews_rating_idx');
            });
        } else {
            Schema::table('reviews', function (Blueprint $table) {
                if (!Schema::hasColumn('reviews', 'softDeletes')) {
                    $table->softDeletes();
                }
                // Add indexes if not exists
                $table->index(['reviewable_type', 'reviewable_id', 'is_approved'], 'reviews_reviewable_idx');
                $table->index('rating', 'reviews_rating_idx');
            });
        }

        // Review votes table
        if (!Schema::hasTable('review_votes')) {
            Schema::create('review_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('review_id')->constrained()->onDelete('cascade');
                $table->boolean('is_helpful');
                $table->timestamps();
                $table->softDeletes();

                // Prevent duplicate votes
                $table->unique(['user_id', 'review_id'], 'review_votes_user_review_unique');
            });
        } else {
            Schema::table('review_votes', function (Blueprint $table) {
                if (!Schema::hasColumn('review_votes', 'softDeletes')) {
                    $table->softDeletes();
                }
                $table->unique(['user_id', 'review_id'], 'review_votes_user_review_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('review_votes');
        Schema::dropIfExists('reviews');
    }
};