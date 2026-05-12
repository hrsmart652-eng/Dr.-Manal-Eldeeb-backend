<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource
{
    /**
     * Transform the resource into an array for instructor listings.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'full_name' => $this->full_name,
            'title' => $this->title,
            'specialization' => $this->specialization_ar,
            'specialization_ar' => $this->specialization_ar,
            'specialization_en' => $this->specialization_en,
            'bio_short' => \Str::limit($this->bio_ar, 150),
            
            'avatar' => $this->user->avatar 
                ? asset('storage/' . $this->user->avatar) 
                : null,
            
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
        ];
    }
}