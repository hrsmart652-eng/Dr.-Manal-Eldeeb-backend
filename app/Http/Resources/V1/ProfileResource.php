
<?php
namespace App\Http\Resources\V1;


use Illuminate\Http\Resources\Json\JsonResource;
class ProfiletResource extends JsonResource
{
// ProfileResource.php

public function toArray($request)
{
    return [
        'name' => $this->name,
        'email' => $this->email,
        'bio' => $this->bio,
        'image' => $this->image,

        'courses' => CourseResource::collection($this->whenLoaded('courses')),
        'books' => BookResource::collection($this->whenLoaded('books')),
    ];
}}