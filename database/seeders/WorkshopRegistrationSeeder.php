<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Illuminate\Database\Seeder;

class WorkshopRegistrationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. جلب أو إنشاء مستخدم وورشة
        $user = User::first() ?: User::factory()->create();
        $workshop = Workshop::first() ?: Workshop::factory()->create();

        // 2. تعريف البيانات التي نريد إدخالها
        // لاحظي: لا يمكن تسجيل نفس المستخدم في نفس الورشة مرتين
        $registrations = [
            [
                'user_id'     => $user->id,
                'workshop_id' => $workshop->id,
                'status'      => 'confirmed',
            ],
            // إذا أردتِ إضافة سجل آخر، يجب أن يكون لمستخدم مختلف أو ورشة مختلفة
        ];

        // 3. استخدام updateOrCreate بدلاً من create
        foreach ($registrations as $reg) {
            WorkshopRegistration::updateOrCreate(
                [
                    'user_id'     => $reg['user_id'],
                    'workshop_id' => $reg['workshop_id']
                ],
                [
                    'status'     => $reg['status'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->command->info('Workshop registrations seeded successfully!');
    }
}