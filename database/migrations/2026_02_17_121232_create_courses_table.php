<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('description_ar');
            $table->text('description_en')->nullable();
            $table->string('slug');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->string('thumbnail')->nullable();
            $table->string('video_intro')->nullable();
            $table->string('video_provider')->nullable(); // youtube, vimeo, local
            $table->decimal('price', 10, 2)->unsigned()->default(0);
            $table->decimal('discount_price', 10, 2)->unsigned()->nullable();
            $table->unsignedTinyInteger('discount_percentage')->nullable();
            $table->timestamp('discount_ends_at')->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->enum('type', ['online', 'offline', 'hybrid'])->default('online');
            $table->integer('duration_hours')->default(0);
            $table->integer('total_lectures')->default(0);
            $table->integer('enrolled_students')->default(0);
            $table->integer('max_students')->nullable(); // Enrollment limit
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);
            $table->json('requirements')->nullable();
            $table->json('what_you_learn')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('has_certificate')->default(true);
            $table->string('certificate_template')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_free')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['slug', 'instructor_id']);

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index('is_featured');
            $table->index('instructor_id');
            $table->index('starts_at');
            $table->index('rating'); //to make which course has high rating

            


        });

        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->index(['course_id', 'order']);

        });

        Schema::create('lectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('course_sections')->onDelete('cascade');
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->enum('type', ['video', 'article', 'quiz', 'assignment', 'file', 'live_session']);
            $table->string('content_url')->nullable();
            $table->string('video_provider')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_downloadable')->default(false);
            $table->integer('order')->default(0);
            $table->json('resources')->nullable(); // PDFs, links, etc.
            $table->timestamp('available_at')->nullable();
            $table->timestamps();
             $table->index(['section_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lectures');
        Schema::dropIfExists('course_sections');
        Schema::dropIfExists('courses');
    }
};