<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'payment_id',
        'user_id',
        'type',
        'amount',
        'currency',
        'status',
        'description',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'payment' => 'دفع',
            'refund' => 'استرداد',
            'fee' => 'رسوم',
            'payout' => 'سحب',
            default => $this->type,
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'قيد الانتظار',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            default => $this->status,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public static function generateNumber()
    {
        do {
            $number = 'TX-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        } while (self::where('transaction_number', $number)->exists());

        return $number;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = self::generateNumber();
            }
        });
    }
}
