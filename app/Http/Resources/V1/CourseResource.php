<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
         return [
            'id' => $this->id,
            'title' => $this->title_ar,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'slug' => $this->slug,
            'description' => $this->description_ar,
            'description_short' => \Str::limit($this->description_ar, 150),
            
            // Media
            'thumbnail' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            
            // Pricing
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'final_price' => (float) $this->final_price,
            'has_active_discount' => $this->has_active_discount,
            'discount_ends_at' => $this->discount_ends_at?->toISOString(),
            
            // Course Info
            'level' => $this->level,
            'level_text' => $this->level_text,
            'type' => $this->type,
            'duration_hours' => $this->duration_hours,
            'total_lectures' => $this->total_lectures,
            'enrolled_students' => $this->enrolled_students,
            
            // Rating
            'rating' => (float) $this->rating,
            'total_reviews' => $this->total_reviews,
            
            // Flags
            'is_featured' => $this->is_featured,
            'has_certificate' => $this->has_certificate,
            
            // Dates
            'published_at' => $this->published_at?->toISOString(),
            
            // Relations
            'instructor' => $this->whenLoaded('instructor', function () {
                return [
                    'id' => $this->instructor->id,
                    'name' => $this->instructor->user->name,
                    'title' => $this->instructor->title,
                    'avatar' => $this->instructor->user->avatar 
                        ? asset('storage/' . $this->instructor->user->avatar) 
                        : null,
                    'rating' => (float) $this->instructor->rating,
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
 
