<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('title')->nullable(); // Dr., Prof., etc.
            $table->text('bio_ar');
            $table->text('bio_en')->nullable();
            $table->string('specialization_ar');
            $table->string('specialization_en')->nullable();
            $table->json('education')->nullable(); // Array of degrees
            $table->json('certifications')->nullable();
            $table->json('social_links')->nullable();
            $table->integer('experience_years')->default(0);
             $table->decimal('rating', 3, 2)->nullable();
            $table->integer('total_students')->default(0);
            $table->integer('total_courses')->default(0);
            $table->integer('total_books')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('available_for_consultation')->default(true);
             $table->decimal('consultation_price', 10, 2)->unsigned()->nullable();
            $table->timestamps();
            
            $table->index('is_featured');
            $table->index('available_for_consultation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};