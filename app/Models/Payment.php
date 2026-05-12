<?php

namespace App\Models;

use App\Models\Booking;
use App\Models\BookPurchase;
use App\Models\Enrollment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkshopRegistration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'payment_number',
        'user_id',
        'payable_type',
        'payable_id',
        'amount',
        'currency',
        'fee',
        'net_amount',
        'payment_method',
        'status',
        'gateway',
        'gateway_payment_id',
        'gateway_order_id',
        'gateway_response',
        'card_last4',
        'card_brand',
        'payer_email',
        'payer_name',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'metadata',
        'ip_address',
        'user_agent',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'metadata' => 'array',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'قيد الانتظار',
            'processing' => 'قيد المعالجة',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            'refunded' => 'مسترد',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }

    public function getPaymentMethodTextAttribute()
    {
        return match($this->payment_method) {
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'credit_card' => 'بطاقة ائتمان',
            'bank_transfer' => 'تحويل بنكي',
            'cash' => 'نقداً',
            default => $this->payment_method,
        };
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsRefundableAttribute()
    {
        // Can refund if completed and not already refunded
        return $this->status === 'completed' 
            && is_null($this->refunded_at)
            && $this->paid_at->diffInDays(now()) <= 30; // 30 days refund policy
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function markAsPaid($gatewayPaymentId = null, $gatewayResponse = null)
    {
        $this->update([
            'status' => 'completed',
            'gateway_payment_id' => $gatewayPaymentId ?? $this->gateway_payment_id,
            'gateway_response' => $gatewayResponse ?? $this->gateway_response,
            'paid_at' => now(),
        ]);

        // Create transaction record
        $this->transactions()->create([
            'transaction_number' => Transaction::generateNumber(),
            'user_id' => $this->user_id,
            'type' => 'payment',
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => 'completed',
            'description' => 'Payment completed',
        ]);

        // Update payable status (enrollment, booking, etc)
        $this->updatePayableStatus();
    }

    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'metadata' => array_merge($this->metadata ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toISOString(),
            ]),
        ]);

        $this->transactions()->create([
            'transaction_number' => Transaction::generateNumber(),
            'user_id' => $this->user_id,
            'type' => 'payment',
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => 'failed',
            'description' => 'Payment failed: ' . $reason,
        ]);
    }

    public function refund($amount = null, $reason = null)
    {
        if (!$this->is_refundable) {
            return false;
        }

        $refundAmount = $amount ?? $this->amount;

        $this->update([
            'status' => 'refunded',
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);

        $this->transactions()->create([
            'transaction_number' => Transaction::generateNumber(),
            'user_id' => $this->user_id,
            'type' => 'refund',
            'amount' => -$refundAmount,
            'currency' => $this->currency,
            'status' => 'completed',
            'description' => 'Refund: ' . $reason,
        ]);

        // Update payable status
        if ($this->payable_type === Enrollment::class) {
            $this->payable->update(['payment_status' => 'refunded']);
        } elseif ($this->payable_type === BookPurchase::class) {
            $this->payable->update(['payment_status' => 'refunded']);
        } elseif ($this->payable_type === Booking::class) {
            $this->payable->update(['payment_status' => 'refunded']);
        }

        return true;
    }

    private function updatePayableStatus()
    {
        if ($this->payable_type === Enrollment::class) {
            $this->payable->update([
                'payment_status' => 'completed',
                'status' => 'active',
            ]);
        } elseif ($this->payable_type === BookPurchase::class) {
            $this->payable->update([
                'payment_status' => 'completed',
            ]);
        } elseif ($this->payable_type === Booking::class) {
            $this->payable->update([
                'payment_status' => 'completed',
                'status' => 'confirmed',
            ]);
        }elseif ($this->payable_type === WorkshopRegistration::class) {
            $this->payable->update([
                'payment_status' => 'completed',
                'status' => 'confirmed',
                // To be used for certificate eligibility
        'certificate_issued' => true,
        'certificate_number' => 'CERT-' . strtoupper(uniqid()),
        'certificate_issued_at' => now(),
            ]);

      //increment workshop registered count
        if ($this->payable->workshop) {
            $this->payable->workshop->increment('registered_participants'); 
           
        }
        }
    }

    public static function generateNumber()
    {
        do {
            $number = 'PY-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        } while (self::where('payment_number', $number)->exists());

        return $number;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generateNumber();
            }

            // Calculate net amount
            $payment->net_amount = $payment->amount - $payment->fee;
        });
    }
}