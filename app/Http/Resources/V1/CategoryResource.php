<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name_ar,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'description' => $this->description_ar,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            
            'type' => $this->type,
            'type_text' => $this->type_text,
            
            'icon' => $this->icon_url,
            'image' => $this->image_url,
            'color' => $this->color,
            
            'order' => $this->order,
            
            // Counts (if loaded)
            'courses_count' => $this->when(
                isset($this->courses_count),
                $this->courses_count
            ),
            'books_count' => $this->when(
                isset($this->books_count),
                $this->books_count
            ),
            
            // Hierarchy
            'parent_id' => $this->parent_id,
            'has_children' => $this->has_children,
            
            'parent' => $this->when(
                $this->relationLoaded('parent') && $this->parent,
                function () {
                    return [
                        'id' => $this->parent->id,
                        'name' => $this->parent->name_ar,
                        'slug' => $this->parent->slug,
                    ];
                }
            ),
            
            'children' => $this->when(
                $this->relationLoaded('allChildren'),
                function () {
                    return CategoryResource::collection($this->allChildren);
                }
            ),

            'active_children' => $this->when(
    $this->relationLoaded('activeChildren'),
    function () {
        return CategoryResource::collection($this->activeChildren);
    }),
            
            'breadcrumb' => $this->when(
                $request->get('with_breadcrumb'),
                $this->breadcrumb
            ),
            
            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
        ];
    }
}