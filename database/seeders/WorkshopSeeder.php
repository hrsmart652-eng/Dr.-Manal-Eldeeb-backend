<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workshop;
use App\Models\Instructor;
use App\Models\Category;

class WorkshopSeeder extends Seeder
{
    public function run(): void
    {
        // 1. جلب أول مدرب وأول تصنيف متاحين
        $instructor = Instructor::first();
        $category = Category::first();

        // 2. التحقق من وجود بيانات قبل البدء لتجنب خطأ الـ Foreign Key
        if (!$instructor || !$category) {
            $this->command->error('يجب إضافة Instructor و Category أولاً قبل تشغيل WorkshopSeeder!');
            return;
        }

        // --- الورشة الأولى ---
        // استخدمنا updateOrCreate لتجنب خطأ Duplicate Entry في الـ slug
        Workshop::updateOrCreate(
            ['slug' => 'transformational-leadership-workshop'],
            [
                'category_id'             => $category->id,
                'instructor_id'           => $instructor->id,
                'title_ar'                => 'ورشة عمل القيادة التحويلية',
                'title_en'                => 'Transformational Leadership Workshop',
                'description_ar'          => 'ورشة عمل شاملة تهدف إلى تطوير مهارات القيادة التحويلية.',
                'description_en'          => 'A comprehensive workshop to develop transformational leadership skills.',
                'objectives'              => json_encode(['تعزيز مهارات القيادة', 'تطوير التفكير الاستراتيجي']),
                'agenda'                  => json_encode(['جلسة 1: مقدمة', 'جلسة 2: تطبيق عملي']),
                'thumbnail'               => null,
                'type'                    => 'multi_session',
                'mode'                    => 'online',
                'location'                => 'Zoom',
                'meeting_link'            => 'https://zoom.us/example',
                'start_date'              => '2026-05-15 18:00:00',
                'end_date'                => '2026-05-18 21:00:00',
                'duration_hours'          => 12,
                'max_participants'        => 50,
                'registered_participants' => 25,
                'price'                   => 350,
                'early_bird_price'        => 280,
                'early_bird_deadline'     => '2026-05-01 00:00:00',
                'includes'                => json_encode(['شهادة حضور', 'مواد تدريبية']),
                'requirements'            => json_encode(['معرفة أساسية بالقيادة']),
                'certificate_available'   => true,
                'rating'                  => 4.8,
                'total_reviews'           => 10,
                'status'                  => 'published',
                'is_featured'             => true,
                'meta_title'              => 'ورشة القيادة التحويلية',
                'meta_description'        => 'ورشة عمل لتطوير مهارات القيادة التحويلية عبر الإنترنت.',
            ]
        );

        // --- الورشة الثانية ---
        Workshop::updateOrCreate(
            ['slug' => 'time-management-workshop'],
            [
                'category_id'             => $category->id, // استخدمنا المتغير بدلاً من رقم ثابت 1
                'instructor_id'           => $instructor->id, // استخدمنا المتغير بدلاً من رقم ثابت 1
                'title_ar'                => 'ورشة إدارة الوقت',
                'title_en'                => 'Time Management Workshop',
                'description_ar'          => 'تعلم استراتيجيات فعالة لإدارة وقتك وزيادة الإنتاجية.',
                'description_en'          => 'Learn effective strategies to manage your time and boost productivity.',
                'objectives'              => json_encode(['تنظيم الوقت', 'زيادة الإنتاجية']),
                'agenda'                  => json_encode(['جلسة 1: الأساسيات', 'جلسة 2: التطبيق العملي']),
                'type'                    => 'single_session',
                'mode'                    => 'in_person',
                'location'                => 'Cairo Training Center',
                'start_date'              => '2026-06-01 10:00:00',
                'end_date'                => '2026-06-01 16:00:00',
                'duration_hours'          => 6,
                'max_participants'        => 40,
                'registered_participants' => 10,
                'price'                   => 200,
                'early_bird_price'        => 180,
                'early_bird_deadline'     => '2026-05-20 00:00:00',
                'includes'                => json_encode(['شهادة حضور']),
                'certificate_available'   => true,
                'rating'                  => 4.5,
                'total_reviews'           => 5,
                'status'                  => 'published',
                'is_featured'             => false,
                'meta_title'              => 'ورشة إدارة الوقت',
                'meta_description'        => 'ورشة عملية لإدارة الوقت وزيادة الإنتاجية.',
            ]
        );
    }
}