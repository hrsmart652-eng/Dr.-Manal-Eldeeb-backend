<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workshop_id')->constrained()->onDelete('cascade');
            
            // Payment
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            
            // Registration Status
            $table->enum('status', ['pending', 'confirmed', 'attended', 'cancelled', 'no_show'])->default('pending');
            
            // Attendance
            $table->boolean('attended')->default(false);
            $table->timestamp('attendance_marked_at')->nullable();
            
            // Certificate
            $table->boolean('certificate_issued')->default(false);
            $table->string('certificate_number')->nullable();
            $table->timestamp('certificate_issued_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            // Cancellation
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'workshop_id']);
            $table->index('registration_number');
            $table->index(['user_id', 'status']);
            $table->index(['workshop_id', 'status']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_registrations');
    }
};