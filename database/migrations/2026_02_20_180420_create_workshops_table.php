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
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('description_ar');
            $table->text('description_en')->nullable();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->string('thumbnail')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            
            $table->decimal('discount_price', 10, 2)->nullable();
                $table->string('currency', 3)->default('USD');  
            $table->enum('type', ['online', 'in_person', 'hybrid'])->default('online');
            $table->string('location')->nullable();
          $table->text('meeting_link')->nullable();
              $table->string('timezone', 50)->default('UTC');   
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            $table->integer('duration_hours');
           
            $table->integer('max_participants')->default(30);
            // $table->integer('enrolled_participants')->default(0);
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
            $table->json('materials')->nullable(); // Workshop materials
            $table->boolean('has_certificate')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'start_datetime']);
                $table->index('category_id');
                $table->index('instructor_id');


        });

        Schema::create('workshop_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workshop_id')->constrained()->onDelete('cascade');
            $table->decimal('price_paid', 10, 2);
            // $table->string('payment_method')->nullable();
        
                $table->enum('payment_method',['cash','card','wallet','bank_transfer'])->nullable();
            $table->string('transaction_id')->nullable();

                $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
                    $table->text('cancellation_reason')->nullable();  // ✅ added here
    $table->timestamp('cancelled_at')->nullable(); 
            $table->enum('attendance_status', ['registered', 'attended', 'absent'])->default('registered');
            $table->timestamps();
              $table->softDeletes();      
            
            $table->unique(['user_id', 'workshop_id']);
                $table->index(['workshop_id', 'payment_status']);     // ✅ added
    $table->index(['workshop_id', 'attendance_status']);
    
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_enrollments');
        Schema::dropIfExists('workshops');
    }
};