<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'rating' => $this->rating,
            'comment' => $this->comment,
            'pros' => $this->pros ?? [],
            'cons' => $this->cons ?? [],
            'is_verified_purchase' => $this->is_verified_purchase,
            'helpful_count' => $this->helpful_count,
            'not_helpful_count' => $this->not_helpful_count,
            'created_at' => $this->created_at->toISOString(),
            'created_at_human' => $this->created_at->diffForHumans(),
            
            // User info
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar 
                        ? asset('storage/' . $this->user->avatar) 
                        : null,
                ];
            }),
            
            // Reviewable info (course or book)
            'reviewable_type' => $this->reviewable_type,
            'reviewable_id' => $this->reviewable_id,
        ];
    }
}
    

