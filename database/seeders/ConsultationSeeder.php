<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Consultation;

class ConsultationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // افترض إن عندك طالب ID = 1
        $studentId =\App\Models\User::first()->id;

        for ($i = 1; $i <= 10; $i++) {
            Consultation::create([
                'student_id' => $studentId,
                'title' => "Test Consultation $i",
                'question' => "This is a sample question number $i for testing purposes.",
                'course_id' => 1, // أو حطي ID لكورس موجود
                'priority' => ['low', 'medium', 'high'][array_rand(['low', 'medium', 'high'])],
                'status' => ['pending', 'answered', 'closed'][array_rand(['pending', 'answered', 'closed'])],
            ]);
        }
    }
}