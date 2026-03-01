<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
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
            'thumbnail' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            'video_intro' => $this->video_intro,
            'video_provider' => $this->video_provider,
            
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
            'max_students' => $this->max_students,
            'available_seats' => $this->available_seats,
            'is_full' => $this->is_full,
            
            // Rating
            'rating' => (float) $this->rating,
            'total_reviews' => $this->total_reviews,
            
            // Learning
            'requirements' => $this->requirements ?? [],
            'what_you_learn' => $this->what_you_learn ?? [],
            'tags' => $this->tags ?? [],
            
            // Features
            'has_certificate' => $this->has_certificate,
            'is_featured' => $this->is_featured,
            
            // Dates
            'published_at' => $this->published_at?->toISOString(),
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            
            // Relations
            'instructor' => $this->whenLoaded('instructor', function () {
                return [
                    'id' => $this->instructor->id,
                    'name' => $this->instructor->user->name,
                    'title' => $this->instructor->title,
                    'bio' => $this->instructor->bio_ar,
                    'specialization' => $this->instructor->specialization_ar,
                    'avatar' => $this->instructor->user->avatar 
                        ? asset('storage/' . $this->instructor->user->avatar) 
                        : null,
                    'rating' => (float) $this->instructor->rating,
                    'total_students' => $this->instructor->total_students,
                    'total_courses' => $this->instructor->total_courses,
                    'experience_years' => $this->instructor->experience_years,
                    'social_links' => $this->instructor->social_links,
                ];
            }),
            
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name_ar,
                    'slug' => $this->category->slug,
                ];
            }),
            
            'sections' => $this->whenLoaded('sections', function () {
                return $this->sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title_ar,
                        'title_ar' => $section->title_ar,
                        'title_en' => $section->title_en,
                        'description' => $section->description_ar,
                        'order' => $section->order,
                        'total_duration' => $section->total_duration,
                        'lecture_count' => $section->lecture_count,
                        'lectures' => $section->lectures->map(function ($lecture) {
                            return [
                                'id' => $lecture->id,
                                'title' => $lecture->title_ar,
                                'title_ar' => $lecture->title_ar,
                                'title_en' => $lecture->title_en,
                                'description' => $lecture->description_ar,
                                'type' => $lecture->type,
                                'type_text' => $lecture->type_text,
                                'duration_minutes' => $lecture->duration_minutes,
                                'is_preview' => $lecture->is_preview,
                                'is_downloadable' => $lecture->is_downloadable,
                                'order' => $lecture->order,
                                'content_url' => $lecture->is_preview ? $lecture->content_url : null,
                                'resources' => $lecture->resources ?? [],
                            ];
                        }),
                    ];
                });
            }),
            
            // Can be enrolled
            'can_enroll' => $this->canEnroll(),
        ];
    }
}

        // return parent::toArray($request);
    

