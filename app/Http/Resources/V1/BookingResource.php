<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {

    // this is the list of purchased book IDs passed from the controller)
    $purchasedIds = $this->additional['purchased_ids'] ?? [];
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            
        'price' => $this->price,
            
            'instructor' => $this->whenLoaded('instructor', function () {
                return [
                    'id' => $this->instructor->id,
                    'name' => $this->instructor->user->name,
                    'title' => $this->instructor->title,
                    'avatar' => $this->instructor->user->avatar 
                        ? asset('storage/' . $this->instructor->user->avatar) 
                        : null,
                ];
            }),
            
            'type' => $this->type,
            'type_text' => $this->type_text,
            'title' => $this->title,
            'description' => $this->description,
            
            'booking_date' => $this->booking_date->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            
            'meeting_type' => $this->meeting_type,
            'meeting_type_text' => $this->meeting_type_text,
            'meeting_link' => $this->meeting_link,
            'location' => $this->location,
            
            'price' => (float) $this->price,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'payment_status_text' => $this->payment_status_text,
            
            'status' => $this->status,
            'status_text' => $this->status_text,
            
            'notes' => $this->notes,
            'instructor_notes' => $this->instructor_notes,
            
            'can_be_cancelled' => $this->canBeCancelled(),
            'is_upcoming' => $this->is_upcoming,
            'is_past' => $this->is_past,
            
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}