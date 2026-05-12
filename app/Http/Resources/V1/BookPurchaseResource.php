<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookPurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->id,
            
            // Book info
            'book' => $this->whenLoaded('book', function () {
                return [
                    'id' => $this->book->id,
                    'title' => $this->book->title_ar,
                    'slug' => $this->book->slug,
                    'cover_image' => $this->book->cover_image 
                        ? asset('storage/' . $this->book->cover_image) 
                        : null,
                    'author' => [
                        'name' => $this->book->author->user->name,
                        'title' => $this->book->author->title,
                    ],
                ];
            }),
            
            // Purchase details
            'format' => $this->format,
            'price_paid' => (float) $this->price_paid,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'payment_status_text' => $this->payment_status_text,
            
            // Download info (for digital)
            'is_digital' => $this->isDigital(),
            'can_download' => $this->canDownload(),
            'download_count' => $this->download_count,
            'max_downloads' => $this->max_downloads,
            'remaining_downloads' => $this->max_downloads - $this->download_count,
            
            // Shipping info (for physical)
            'is_physical' => $this->isPhysical(),
            'shipping_status' => $this->shipping_status,
            'shipping_status_text' => $this->shipping_status_text,
            'tracking_number' => $this->tracking_number,
            
            // Dates
            'purchased_at' => $this->created_at->toISOString(),
        ];
    }
}