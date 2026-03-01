<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\User;
use App\Models\Instructor;
use App\Models\Category;
use Illuminate\Database\Seeder;
// use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Create instructor user
        $instructorUser = User::firstOrCreate(
            ['email' => 'instructor@leadersacademy.com'],
            [
                'name' => 'د. منال الديب',
                'password' => bcrypt('password'),
                'type' => 'instructor',
                'email_verified_at' => now(),
            ]
        );

        // Create instructor profile
        $instructor = Instructor::firstOrCreate(
            ['user_id' => $instructorUser->id],
            [
                'title' => 'دكتوراه',
                'bio_ar' => 'خبيرة في القيادة التحويلية والتطوير التنظيمي مع أكثر من 15 عاماً من الخبرة',
                'specialization_ar' => 'القيادة التحويلية والتطوير التنظيمي',
                'experience_years' => 15,
                'rating' => 4.9,
                'total_students' => 5000,
                'is_featured' => true,
            ]
        );

        // Get category
        $category = Category::where('slug', 'leadership-management')->first();

        // Create course
        $course = Course::create([
            'title_ar' => 'دورة القيادة التحويلية - المستوى المتقدم',
            'title_en' => 'Transformational Leadership - Advanced Level',
            'slug' => 'transformational-leadership-advanced',
            'description_ar' => 'برنامج احترافي متقدم لتطوير مهارات القيادة التحويلية واستراتيجيات التغيير المؤسسي',
            'description_en' => 'Advanced professional program for developing transformational leadership skills',
            'category_id' => $category->id,
            'instructor_id' => $instructor->id,
            'price' => 3500,
            'level' => 'advanced',
            'status' => 'published',
            'type' => 'online',
            'duration_hours' => 40,
            'total_lectures' => 0, // Will update after creating lectures
            'rating' => 4.8,
            'total_reviews' => 340,
            'enrolled_students' => 1200,
            'requirements' => [
                'خبرة سابقة في مجال القيادة',
                'إتمام المستوى الأساسي في القيادة',
            ],
            'what_you_learn' => [
                'مهارات القيادة التحويلية المتقدمة',
                'استراتيجيات إدارة التغيير المؤسسي',
                'بناء فرق العمل عالية الأداء',
                'تطوير الرؤية الاستراتيجية',
            ],
            'is_featured' => true,
            'has_certificate' => true,
            'published_at' => now()->subMonths(6),
        ]);

        // Create sections and lectures
        $section1 = CourseSection::create([
            'course_id' => $course->id,
            'title_ar' => 'مقدمة في القيادة التحويلية',
            'title_en' => 'Introduction to Transformational Leadership',
            'order' => 1,
        ]);

        $lectures = [
            [
                'title_ar' => 'ما هي القيادة التحويلية؟',
                'type' => 'video',
                'duration_minutes' => 15,
                'is_preview' => true,
            ],
            [
                'title_ar' => 'أركان القيادة التحويلية الأربعة',
                'type' => 'video',
                'duration_minutes' => 20,
                'is_preview' => true,
            ],
            [
                'title_ar' => 'الفرق بين القيادة التحويلية والتقليدية',
                'type' => 'video',
                'duration_minutes' => 18,
                'is_preview' => false,
            ],
        ];

        $lectureCount = 0;
        foreach ($lectures as $index => $lectureData) {
            Lecture::create(array_merge($lectureData, [
                'section_id' => $section1->id,
                'order' => $index + 1,
            ]));
            $lectureCount++;
        }

        // Create section 2
        $section2 = CourseSection::create([
            'course_id' => $course->id,
            'title_ar' => 'استراتيجيات التأثير والإلهام',
            'title_en' => 'Influence and Inspiration Strategies',
            'order' => 2,
        ]);

        $lectures2 = [
            [
                'title_ar' => 'بناء الثقة مع الفريق',
                'type' => 'video',
                'duration_minutes' => 25,
            ],
            [
                'title_ar' => 'تحفيز الموظفين نحو الرؤية',
                'type' => 'video',
                'duration_minutes' => 22,
            ],
        ];

        foreach ($lectures2 as $index => $lectureData) {
            Lecture::create(array_merge($lectureData, [
                'section_id' => $section2->id,
                'order' => $index + 1,
            ]));
            $lectureCount++;
        }

        // Update total lectures
        $course->update(['total_lectures' => $lectureCount]);

        // Create a second course (free)
        Course::create([
            'title_ar' => 'مقدمة في القيادة',
            'title_en' => 'Introduction to Leadership',
            'slug' => 'introduction-leadership',
            'description_ar' => 'دورة تمهيدية مجانية للتعريف بأساسيات القيادة',
            'category_id' => $category->id,
            'instructor_id' => $instructor->id,
            'price' => 0,
            'level' => 'beginner',
            'status' => 'published',
            'type' => 'online',
            'duration_hours' => 5,
            'total_lectures' => 8,
            'rating' => 4.5,
            'enrolled_students' => 3500,
            'is_featured' => false,
            'has_certificate' => false,
            'published_at' => now()->subMonth(),
        ]);
    }
}
