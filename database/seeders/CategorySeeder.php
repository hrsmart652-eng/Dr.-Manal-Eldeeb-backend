<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_ar' => 'القيادة والإدارة',
                'name_en' => 'Leadership & Management',
                'slug' => 'leadership-management',
                'type' => 'course',
                'is_active' => true,
            ],
            [
                'name_ar' => 'التطوير الذاتي',
                'name_en' => 'Self Development',
                'slug' => 'self-development',
                'type' => 'course',
                'is_active' => true,
            ],
            [
                'name_ar' => 'الموارد البشرية',
                'name_en' => 'Human Resources',
                'slug' => 'human-resources',
                'type' => 'course',
                'is_active' => true,
            ],
            [
                'name_ar' => 'المبيعات والتسويق',
                'name_en' => 'Sales & Marketing',
                'slug' => 'sales-marketing',
                'type' => 'course',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
