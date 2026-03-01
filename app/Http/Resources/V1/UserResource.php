<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'type' => $this->type,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'is_active' => $this->is_active,
            'email_verified' => !is_null($this->email_verified_at),
            'created_at' => $this->created_at?->toISOString(),
            
            // Include instructor data if user is instructor
            'instructor' => $this->when(
                $this->type === 'instructor' && $this->relationLoaded('instructor'),
                fn() => new InstructorResource($this->instructor)
            ),
        ];
    }
}
    
