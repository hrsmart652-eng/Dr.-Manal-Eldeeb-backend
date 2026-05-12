<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            
            'payment' => $this->when(
                $this->relationLoaded('payment'),
                function () {
                    return [
                        'id' => $this->payment->id,
                        'payment_number' => $this->payment->payment_number,
                    ];
                }
            ),
            
            'type' => $this->type,
            'type_text' => $this->type_text,
            
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'formatted_amount' => $this->formatted_amount,
            
            'status' => $this->status,
            'status_text' => $this->status_text,
            
            'description' => $this->description,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}