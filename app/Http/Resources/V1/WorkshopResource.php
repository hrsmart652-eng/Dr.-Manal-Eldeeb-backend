<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopResource extends JsonResource
{
      /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
   public function toArray($request)
{
    // return parent::toArray($request);

    return [
        'id'                      => $this->id,
        'title_ar'                => $this->title_ar,
        'title_en'                => $this->title_en,
        'slug'                    => $this->slug,
        'description_ar'          => $this->description_ar,
        'description_en'          => $this->description_en,
        'instructor'              => [
            'id'   => optional($this->instructor)->id,
            'name' => optional(optional($this->instructor)->user)->name,
        ],
        'category_id'             => $this->category_id,
        'type'                    => $this->type,
        'mode'                    => $this->mode,
        'location'                => $this->location,
        'start_date'              => $this->start_date,
        'end_date'                => $this->end_date,
        'duration_hours'          => $this->duration_hours,
        'max_participants'        => $this->max_participants,
        'registered_participants' => $this->registered_participants,
        'available_spots'         => $this->max_participants - $this->registered_participants,
        'price'                   => $this->price,
        'early_bird_price'        => $this->early_bird_price,
        'early_bird_deadline'     => $this->early_bird_deadline,
        'certificate_available'   => $this->certificate_available,
        'rating'                  => $this->rating,
        'total_reviews'           => $this->total_reviews,
        'status'                  => $this->status,
        'is_featured'             => $this->is_featured,
    ];
}
}