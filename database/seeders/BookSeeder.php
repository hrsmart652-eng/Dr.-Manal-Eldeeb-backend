<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Instructor;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = Instructor::first();
        $category = Category::where('type', 'book')->first();

        // If no book category, create one
        if (!$category) {
            $category = Category::create([
                'name_ar' => 'كتب القيادة',
                'name_en' => 'Leadership Books',
                'slug' => 'leadership-books',
                'type' => 'book',
                'is_active' => true,
            ]);
        }

        // Create books
        $books = [
            [
                'title_ar' => 'القيادة التحويلية: دليل شامل',
                'title_en' => 'Transformational Leadership: Complete Guide',
                'slug' => 'transformational-leadership-guide',
                'description_ar' => 'كتاب شامل يغطي جميع جوانب القيادة التحويلية مع أمثلة عملية ودراسات حالة واقعية',
                'category_id' => $category->id,
                'author_id' => $instructor->id,
                'isbn' => '978-1234567890',
                'format' => 'both',
                'digital_price' => 150,
                'physical_price' => 250,
                'publisher' => 'أكاديمية القادة للنشر',
                'publication_date' => now()->subMonths(6),
                'pages' => 350,
                'language' => 'ar',
                'status' => 'published',
                'file_size_mb' => 5,
                'total_sales' => 450,
                'stock_quantity' => 50,
                'rating' => 4.7,
                'total_reviews' => 89,
                'is_featured' => true,
                'published_at' => now()->subMonths(6),
            ],
            
            [
                'title_ar' => 'مهارات القيادة الفعالة',
                'title_en' => 'Effective Leadership Skills',
                'slug' => 'effective-leadership-skills',
                'description_ar' => 'كتاب عملي يركز على المهارات الأساسية للقائد الناجح',
                'category_id' => $category->id,
                'author_id' => $instructor->id,
                'isbn' => '978-0987654321',
                'format' => 'pdf',
                'digital_price' => 0, // Free
                'publisher' => 'أكاديمية القادة للنشر',
                'publication_date' => now()->subMonths(2),
                'pages' => 180,
                'language' => 'ar',
                'status' => 'published',
                'file_size_mb' => 3,
                'total_sales' => 1200,
                'rating' => 4.5,
                'total_reviews' => 234,
                'is_featured' => false,
                'published_at' => now()->subMonths(2),
            ],
        ];

        foreach ($books as $bookData) {
            Book::create($bookData);
        }
    }
}