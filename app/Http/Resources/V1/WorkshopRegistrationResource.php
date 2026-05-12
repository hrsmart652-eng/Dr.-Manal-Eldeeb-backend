<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopRegistrationResource extends JsonResource
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
        'registration_id' => $this->id,
            'registration_date' => $this->created_at->format('Y-m-d H:i'),
            'status' => $this->status ?? 'pending', // حالة التسجيل (مقبول، معلق، إلخ)
            
            // بيانات الطالب (المستخدم)
            'student' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],

            // بيانات الورشة بالتفصيل
            'workshop' => [
                'id' => $this->workshop->id,
                'title' => $this->workshop->title,
                'description' => $this->workshop->description,
                'price' => $this->workshop->price,
                'start_date' => $this->workshop->start_date,
                'instructor_name' => $this->workshop->instructor_name ?? 'N/A',
                'image_url' => $this->workshop->image ? asset('storage/' . $this->workshop->image) : null,
            ],

            // روابط إضافية (اختياري)
            'links' => [
                'self' => route('workshops.show', $this->workshop->id),
            ]
        ];
    }
    }

