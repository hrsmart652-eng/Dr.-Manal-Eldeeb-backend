<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instructor availability schedule
     if (!Schema::hasTable('availability_schedules')) {
    Schema::create('availability_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->enum('day_of_week', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('slot_duration_minutes')->default(60); // Duration per booking
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            // $table->check('end_time > start_time');
            $table->index(['instructor_id', 'day_of_week', 'is_active'], 'idx_instructor_availability');

        $table->string('timezone', 50)->default('UTC'); // e.g. 'Africa/Cairo', 'America/New_York'
            // $table->index(['instructor_id', 'day_of_week', 'is_active']);
           
            $table->unique(
    ['instructor_id', 'day_of_week', 'start_time', 'end_time'], 
    'availability_sched_unique' // الاسم القصير
);  // Prevent double booking for the same time slot based on availability schedule

        });   
}

        // Bookings/Consultations
       if (!Schema::hasTable('bookings')) {
    Schema::create('bookings', function (Blueprint $table) {
    
 

            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['consultation', 'workshop', 'private_session'])->default('consultation');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            
          
        

            $table->enum('meeting_type', ['online', 'in_person'])->default('online');
           
            $table->text('meeting_link')->nullable(); // Zoom, Teams, etc.
            $table->text('location')->nullable(); // For in-person

            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('USD'); // إضافة العملة
            $table->enum('payment_method',['cash','card','wallet','bank_transfer'])->nullable();

            $table->string('transaction_id')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable(); // Student notes
            $table->text('instructor_notes')->nullable();
            $table->softDeletes(); // مهم جداً للـ Production
            $table->timestamps();
            $table->string('timezone', 50)->default('UTC');
            // $table->check('end_time > start_time');
         $table->index(['payment_method', 'payment_status']);



            $table->index(['user_id', 'status']);
            $table->index(['instructor_id', 'booking_date']);
           $table->unique(['instructor_id','booking_date','start_time','end_time']); // Prevent double booking for the same time slot
        

         
        });
       }

        // Blocked time slots (holidays, personal time off)
       if (!Schema::hasTable('blocked_slots')) {
    Schema::create('blocked_slots', function (Blueprint $table) {
        
    

            $table->id();
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('start_time')->nullable(); // null = full day block
            $table->time('end_time')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['instructor_id', 'date']);
            // $table->check('end_time > start_time');

        });
    }
    }
    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('availability_schedules');
    }
};