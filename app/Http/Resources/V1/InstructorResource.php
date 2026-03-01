<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource
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
            'title' => $this->title,
            'bio' => $this->bio_ar,
            'bio_ar' => $this->bio_ar,
            'bio_en' => $this->bio_en,
            'specialization' => $this->specialization_ar,
            'specialization_ar' => $this->specialization_ar,
            'specialization_en' => $this->specialization_en,
            'experience_years' => $this->experience_years,
            'rating' => (float) $this->rating,
            'total_students' => $this->total_students,
            'total_courses' => $this->total_courses,
            'is_featured' => $this->is_featured,
            'available_for_consultation' => $this->available_for_consultation,
            'consultation_price' => $this->consultation_price ? (float) $this->consultation_price : null,
            'social_links' => $this->social_links,
        ];
    }
}
  