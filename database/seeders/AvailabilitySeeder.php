<?php

namespace Database\Seeders;

use App\Models\Instructor;
use App\Models\AvailabilitySchedule;
use App\Models\BlockedSlot;
use Illuminate\Database\Seeder;

class AvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $instructor = Instructor::first();

        if (!$instructor) {
            $this->command->error('❌ No instructor found! Run InstructorSeeder first.');
            return;
        }

        // Clear existing schedules
        AvailabilitySchedule::where('instructor_id', $instructor->id)->delete();
        BlockedSlot::where('instructor_id', $instructor->id)->delete();

        // Create weekly schedule (Sunday to Thursday, 9 AM to 5 PM)
        $workingDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];
        
        foreach ($workingDays as $day) {
            AvailabilitySchedule::create([
                'instructor_id' => $instructor->id,
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'slot_duration_minutes' => 60,
                'is_active' => true,
            ]);
        }

        // Add lunch break (blocked slot for all working days)
        // We'll add a specific date block as example
        BlockedSlot::create([
            'instructor_id' => $instructor->id,
            'date' => now()->addDays(5)->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'reason' => 'استراحة الغداء',
        ]);

        // Add vacation (full day block)
        BlockedSlot::create([
            'instructor_id' => $instructor->id,
            'date' => now()->addDays(10)->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'reason' => 'إجازة',
        ]);

        $this->command->info('✅ Availability schedules created successfully!');
        $this->command->info('   - Working days: Sunday to Thursday');
        $this->command->info('   - Hours: 9:00 AM to 5:00 PM');
        $this->command->info('   - Slot duration: 60 minutes');
        $this->command->info('   - Sample blocked slots added');
    }
}
