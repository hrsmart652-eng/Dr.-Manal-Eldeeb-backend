<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->enum('status', ['active', 'completed', 'expired', 'cancelled'])->default('active');
            $table->integer('progress_percentage')->default(0);
            $table->integer('completed_lectures')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'course_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('lecture_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lecture_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->boolean('is_completed')->default(false);
           

            $table->unsignedInteger('watch_time_seconds')->default(0);
            $table->unsignedInteger('total_duration_seconds')->default(0);
            $table->unsignedTinyInteger('completion_percentage')->default(0);



            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'lecture_id']);
        });

        // Book Purchases
        Schema::create('book_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->enum('format', ['digital', 'physical', 'both']);
         

            $table->decimal('price_paid', 10, 2)->unsigned();
             $table->integer('quantity')->default(1);

            $table->string('payment_method');
            $table->string('transaction_id')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('download_link')->nullable();
        

            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('max_downloads')->default(3);

            // Shipping info for physical books
            $table->text('shipping_address')->nullable();
            $table->string('tracking_number')->nullable();
            $table->enum('shipping_status', ['pending', 'shipped', 'delivered'])->nullable();
            $table->timestamps();
            

            $table->index(['user_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_purchases');
        Schema::dropIfExists('lecture_progress');
        Schema::dropIfExists('enrollments');
    }
};