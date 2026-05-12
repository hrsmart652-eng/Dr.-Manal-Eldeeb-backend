<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'full_name' => $this->full_name,
            'title' => $this->title,
            'bio' => $this->bio_ar,
            'bio_ar' => $this->bio_ar,
            'bio_en' => $this->bio_en,
            'specialization' => $this->specialization_ar,
            'specialization_ar' => $this->specialization_ar,
            'specialization_en' => $this->specialization_en,
            
            'avatar' => $this->user->avatar 
                ? asset('storage/' . $this->user->avatar) 
                : null,
            
            'education' => $this->education ?? [],
            'certifications' => $this->certifications ?? [],
            'social_links' => $this->social_links ?? [],
            
            'experience_years' => $this->experience_years,
            'rating' => (float) $this->rating,
            'total_students' => $this->total_students,
            'total_courses' => $this->total_courses,
            'total_books' => $this->total_books,
            
            'is_featured' => $this->is_featured,
            'available_for_consultation' => $this->available_for_consultation,
            'consultation_price' => $this->consultation_price 
                ? (float) $this->consultation_price 
                : null,
            
            'email' => $this->user->email,
            'phone' => $this->user->phone,
        ];
    }
}