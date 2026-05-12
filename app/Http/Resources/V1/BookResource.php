<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title_ar,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'slug' => $this->slug,
            'description' => \Str::limit($this->description_ar, 200),
            
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
            
            // Stats
            'rating' => (float) $this->rating,
            'total_reviews' => $this->total_reviews,
            'total_sales' => $this->total_sales,
            'in_stock' => $this->in_stock,
            
            // Flags
            'is_featured' => $this->is_featured,
            'is_digital' => $this->is_digital,
            'is_physical' => $this->is_physical,
            
            // Relations
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->user->name,
                    'title' => $this->author->title,
                    'avatar' => $this->author->user->avatar 
                        ? asset('storage/' . $this->author->user->avatar) 
                        : null,
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