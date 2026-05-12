<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'question' => $this->question,
            'answer' => $this->answer,
            'status' => $this->status,
            'priority' => $this->priority,
            'course_title' => $this->course?->title,
            'instructor_name' => $this->instructor?->name,
            'created_at' => $this->created_at,
            'answered_at' => $this->answered_at,
        ];
    }
}