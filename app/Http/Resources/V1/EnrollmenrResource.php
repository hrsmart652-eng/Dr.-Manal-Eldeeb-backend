<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmenrResource extends JsonResource
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
            'enrollment_id' => $this->id,
            
            // Course info
            'course' => $this->whenLoaded('course', function () {
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title_ar,
                    'slug' => $this->course->slug,
                    'thumbnail' => $this->course->thumbnail 
                        ? asset('storage/' . $this->course->thumbnail) 
                        : null,
                    'level' => $this->course->level,
                    'level_text' => $this->course->level_text,
                    'total_lectures' => $this->course->total_lectures,
                    'duration_hours' => $this->course->duration_hours,
                    'instructor' => [
                        'name' => $this->course->instructor->user->name,
                        'title' => $this->course->instructor->title,
                        'avatar' => $this->course->instructor->user->avatar 
                            ? asset('storage/' . $this->course->instructor->user->avatar) 
                            : null,
                    ],
                ];
            }),
            
            // Progress
            'progress_percentage' => $this->progress_percentage,
            'completed_lectures' => $this->completed_lectures,
            
            // Status
            'status' => $this->status,
            'status_text' => $this->status_text,
            'payment_status' => $this->payment_status,
            'payment_status_text' => $this->payment_status_text,
            
            // Payment
            'price_paid' => (float) $this->price_paid,
            'payment_method' => $this->payment_method,
            
            // Dates
            'enrolled_at' => $this->created_at->toISOString(),
            'last_accessed_at' => $this->last_accessed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            
            // Flags
            'is_active' => $this->isActive(),
            'is_completed' => $this->isCompleted(),
        ];
    }
}
  
