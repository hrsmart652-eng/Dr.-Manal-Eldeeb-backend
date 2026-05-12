<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

             return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'objectives'  => $this->objectives,
            'prerequisites' => $this->prerequisites,
            'instructor'  => [
                'id'   => $this->instructor->id ?? null,
                'name' => $this->instructor->user->name ?? null,
                'title'=> $this->instructor->title ?? null,
                'specialization' => $this->instructor->specialization ?? null,
            ],
            'type'        => $this->type,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date,
            'duration_hours' => $this->duration_hours,
            'sessions'    => $this->sessions->map(function($session) {
                return [
                    'id'          => $session->id,
                    'title'       => $session->title,
                    'session_date'=> $session->session_date,
                    'start_time'  => $session->start_time,
                    'end_time'    => $session->end_time,
                ];
            }),
            'price'       => $this->price,
            'final_price' => $this->final_price,
            'early_bird_deadline' => $this->early_bird_deadline,
            'max_attendees' => $this->max_attendees,
            'registered_count' => $this->registered_count,
            'available_spots' => $this->available_spots,
            'certificate_provided' => $this->certificate_provided,
            'rating'      => $this->rating,
            'can_register'=> $this->can_register,
        ];
    }
    }

