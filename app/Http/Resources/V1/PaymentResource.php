<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            
            // User info
            'user_id' => $this->user_id,
            
            // Payable (what was paid for)
            'payable_type' => class_basename($this->payable_type),
            'payable_id' => $this->payable_id,
            'payable' => $this->when(
                $this->relationLoaded('payable'),
                function () {
                    return [
                        'type' => class_basename($this->payable_type),
                        'id' => $this->payable_id,
                        'name' => $this->getPayableName(),
                    ];
                }
            ),
            
            // Amounts
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'fee' => (float) $this->fee,
            'net_amount' => (float) $this->net_amount,
            
            // Payment details
            'payment_method' => $this->payment_method,
            'payment_method_text' => $this->payment_method_text,
            'status' => $this->status,
            'status_text' => $this->status_text,
            
            // Gateway info
            'gateway' => $this->gateway,
            'gateway_payment_id' => $this->gateway_payment_id,
            
            // Card info (if applicable)
            'card_last4' => $this->card_last4,
            'card_brand' => $this->card_brand,
            
            // Payer info
            'payer_email' => $this->payer_email,
            'payer_name' => $this->payer_name,
            
            // Refund info
            'refund_amount' => $this->refund_amount ? (float) $this->refund_amount : null,
            'refund_reason' => $this->refund_reason,
            'refunded_at' => $this->refunded_at?->toISOString(),
            'is_refundable' => $this->is_refundable ?? false,
            
            // Metadata
            'metadata' => $this->metadata,
            'ip_address' => $this->ip_address,
            
            // Timestamps
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Related transactions
            'transactions' => $this->when(
                $this->relationLoaded('transactions'),
                TransactionResource::collection($this->transactions)
            ),
        ];
    }

    /**
     * Get payable name for display
     */
    private function getPayableName()
    {
        if (!$this->payable) {
            return null;
        }

        $type = class_basename($this->payable_type);

        if ($type === 'Enrollment' && isset($this->payable->course)) {
            return $this->payable->course->title_ar;
        }

        if ($type === 'BookPurchase' && isset($this->payable->book)) {
            return $this->payable->book->title_ar;
        }

        if ($type === 'Booking' && isset($this->payable->instructor)) {
            return 'حجز مع ' . $this->payable->instructor->user->name;
        }

        return null;
    }
}