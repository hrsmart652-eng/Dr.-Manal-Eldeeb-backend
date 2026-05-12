<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title_ar,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'slug' => $this->slug,
            'description' => $this->description_ar,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            
            // Media
            'cover_image' => $this->cover_image ? asset('storage/' . $this->cover_image) : null,
            
            // Pricing
            'digital_price' => (float) $this->digital_price,
            'physical_price' => $this->physical_price ? (float) $this->physical_price : null,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'final_price' => (float) $this->final_price,
            'has_discount' => $this->has_discount,
            'discount_percentage' => $this->discount_percentage,
            
            // Book Info
            'format' => $this->format,
            'isbn' => $this->isbn,
            'pages' => $this->pages,
            'publisher' => $this->publisher,
            'publication_date' => $this->publication_date?->toDateString(),
            'language' => $this->language,
            'file_size_mb' => $this->file_size_mb,
            
            // Stats
            'rating' => (float) $this->rating,
            'total_reviews' => $this->total_reviews,
            'total_sales' => $this->total_sales,
            'stock_quantity' => $this->stock_quantity,
            'in_stock' => $this->in_stock,
            
            // Flags
            'is_featured' => $this->is_featured,
            'is_digital' => $this->is_digital,
            'is_physical' => $this->is_physical,
            
            // Sample pages
            'has_sample_pages' => !empty($this->sample_pages),
            'sample_pages_count' => count($this->sample_pages ?? []),
            
            // Dates
            'published_at' => $this->published_at?->toISOString(),
            
            // Relations
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->user->name,
                    'title' => $this->author->title,
                    'bio' => $this->author->bio_ar,
                    'specialization' => $this->author->specialization_ar,
                    'avatar' => $this->author->user->avatar 
                        ? asset('storage/' . $this->author->user->avatar) 
                        : null,
                    'rating' => (float) $this->author->rating,
                    'total_books' => $this->author->total_books,
                ];
            }),
            
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name_ar,
                    'slug' => $this->category->slug,
                ];
            }),
        ];
    }
}