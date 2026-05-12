<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            
            // Basic Info
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            
            // Details
            $table->json('objectives')->nullable();
            $table->json('agenda')->nullable();
            $table->string('thumbnail')->nullable();
            
            // Type & Mode
            $table->enum('type', ['single_session', 'multi_session', 'intensive', 'seminar'])->default('single_session');
            $table->enum('mode', ['online', 'in_person', 'hybrid'])->default('online');
            
            // Location
            $table->string('location')->nullable();
            $table->text('venue_details')->nullable();
            $table->string('meeting_link')->nullable();
            
            // Schedule
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->decimal('duration_hours', 5, 1)->default(0);
            
            // Capacity
            $table->integer('max_participants')->default(0);
            $table->integer('registered_participants')->default(0);
            
            // Pricing
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('early_bird_price', 10, 2)->nullable();
            $table->timestamp('early_bird_deadline')->nullable();
            
            // Additional Info
            $table->json('includes')->nullable();
            $table->json('requirements')->nullable();
            $table->boolean('certificate_available')->default(false);
            
            // Stats
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            
            // Status
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->boolean('is_featured')->default(false);
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('status');
            $table->index('start_date');
            $table->index(['category_id', 'status']);
            $table->index(['instructor_id', 'status']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};