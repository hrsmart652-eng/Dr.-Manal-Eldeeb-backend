<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availability_schedules', function (Blueprint $table) {
            // إضافة soft deletes لو مش موجود
            if (!Schema::hasColumn('availability_schedules', 'deleted_at')) {
                $table->softDeletes();
            }

            // إضافة unique index بالاسم القصير لتجنب Error 1059
            $table->unique(
                ['instructor_id', 'day_of_week', 'start_time', 'end_time'], 
                'availability_sched_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('availability_schedules', function (Blueprint $table) {
            // لو هترجعي التعديلات
            $table->dropUnique('availability_sched_unique');

            if (Schema::hasColumn('availability_schedules', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};