<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Instructor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        // Create first instructor user
        $instructorUser1 = User::firstOrCreate(
            ['email' => 'instructor@leadersacademy.com'],
            [
                'name' => 'د. منال الديب',
                'password' => Hash::make('password'),
                'type' => 'instructor',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Create instructor profile
        $instructor1 = Instructor::updateOrCreate(
            ['user_id' => $instructorUser1->id],
            [
                'title' => 'دكتوراه',
                'bio_ar' => 'خبيرة في القيادة التحويلية والتطوير التنظيمي مع أكثر من 15 عاماً من الخبرة في تدريب القادة والمدراء التنفيذيين. حاصلة على دكتوراه في إدارة الأعمال من جامعة هارفارد.',
                'bio_en' => 'Expert in transformational leadership and organizational development with over 15 years of experience training leaders and executives.',
                'specialization_ar' => 'القيادة التحويلية، التطوير التنظيمي، التخطيط الاستراتيجي',
                'specialization_en' => 'Transformational Leadership, Organizational Development, Strategic Planning',
                'education' => [
                    'دكتوراه في إدارة الأعمال - جامعة هارفارد',
                    'ماجستير في القيادة التنظيمية - جامعة ستانفورد',
                    'بكالوريوس في علم النفس - جامعة القاهرة',
                ],
                'certifications' => [
                    'معتمد من المعهد الدولي للتدريب والتطوير (ICF)',
                    'مدرب محترف معتمد (CPT)',
                    'خبير في تقييم الشخصية MBTI',
                ],
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/in/manal-aldeeb',
                    'twitter' => 'https://twitter.com/manal_aldeeb',
                    'facebook' => 'https://facebook.com/dr.manal.aldeeb',
                ],
                'experience_years' => 15,
                'rating' => 4.9,
                'total_students' => 5000,
                'total_courses' => 12,
                'total_books' => 3,
                'is_featured' => true,
                'available_for_consultation' => true,
                'consultation_price' => 500,
            ]
        );

        // Create second instructor
        $instructorUser2 = User::firstOrCreate(
            ['email' => 'ahmed@leadersacademy.com'],
            [
                'name' => 'د. أحمد محمود',
                'password' => Hash::make('password'),
                'type' => 'instructor',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        Instructor::updateOrCreate(
            ['user_id' => $instructorUser2->id],
            [
                'title' => 'دكتور',
                'bio_ar' => 'متخصص في التسويق الرقمي وإدارة العلامات التجارية مع خبرة 10 سنوات في الشركات العالمية.',
                'bio_en' => 'Specialist in digital marketing and brand management.',
                'specialization_ar' => 'التسويق الرقمي، إدارة العلامات التجارية',
                'specialization_en' => 'Digital Marketing, Brand Management',
                'experience_years' => 10,
                'rating' => 4.7,
                'total_students' => 3000,
                'total_courses' => 8,
                'is_featured' => false,
                'available_for_consultation' => true,
                'consultation_price' => 350,
            ]
        );

        $this->command->info('✅ Instructors created successfully!');
    }
}
