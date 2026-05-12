<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\BookResource;
use App\Http\Resources\V1\ConsultationResource;
use App\Http\Resources\V1\EnrollmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get enrollments
        $enrollments = $this->enrolledCourses;
        
        // Get all books from all enrolled courses
        $allBooks = $enrollments->flatMap(fn($course) => $course->books ?? collect());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'bio' => $this->bio,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'city' => $this->city,
            'country' => $this->country,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'is_email_verified' => $this->isEmailVerified(),
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // My Enrollments (with books inside each course)
            'enrollments' => [
                'total' => $enrollments->count(),
                'active' => $enrollments->where('status', 'active')->count(),
                'completed' => $enrollments->where('status', 'completed')->count(),
                'expired' => $enrollments->where('status', 'expired')->count(),
                'cancelled' => $enrollments->where('status', 'cancelled')->count(),
                'data' => EnrollmentResource::collection($enrollments),
            ],

            // All Books from all courses
            'books' => [
                'total' => $allBooks->count(),
                'data' => BookResource::collection($allBooks),
            ],

            // My Consultations
            'consultations' => [
                'total' => $this->consultations->count(),
                'pending' => $this->consultations->where('status', 'pending')->count(),
                'answered' => $this->consultations->where('status', 'answered')->count(),
                'closed' => $this->consultations->where('status', 'closed')->count(),
                'data' => ConsultationResource::collection($this->consultations),
            ],
        ];
    }
}