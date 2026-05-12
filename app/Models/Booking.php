<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslation;

class Booking extends Model
{
    use HasFactory,HasTranslation;

    protected $fillable = [
        'booking_number',
        'user_id',
        'instructor_id',
        'type',
        'title',
        'description',
        'booking_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'meeting_type',
        'meeting_link',
        'location',
        'price',
        'payment_method',
        'transaction_id',
        'payment_status',
        'status',
        'cancellation_reason',
        'cancelled_at',
        'notes',
        'instructor_notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'cancelled_at' => 'datetime',
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

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'consultation' => 'استشارة',
            'workshop' => 'ورشة عمل',
            'private_session' => 'جلسة خاصة',
            default => $this->type,
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'قيد الانتظار',
            'confirmed' => 'مؤكد',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'no_show' => 'لم يحضر',
            default => $this->status,
        };
    }

    public function getPaymentStatusTextAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'قيد الانتظار',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            'refunded' => 'مسترد',
            default => $this->payment_status,
        };
    }

    public function getMeetingTypeTextAttribute()
    {
        return match($this->meeting_type) {
            'online' => 'عبر الإنترنت',
            'in_person' => 'حضوري',
            default => $this->meeting_type,
        };
    }

    public function getFullDateTimeAttribute()
    {
        return $this->booking_date->format('Y-m-d') . ' ' . $this->start_time;
    }

    public function getIsUpcomingAttribute()
    {
        $bookingDateTime = new \DateTime($this->full_date_time);
        return $bookingDateTime > now();
    }

    public function getIsPastAttribute()
    {
        $bookingDateTime = new \DateTime($this->full_date_time);
        return $bookingDateTime < now();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeUpcoming($query)
    {
        return $query->where(function($q) {
            $q->where('booking_date', '>', now()->toDateString())
              ->orWhere(function($sq) {
                  $sq->where('booking_date', now()->toDateString())
                     ->where('start_time', '>', now()->toTimeString());
              });
        });
    }

    public function scopePast($query)
    {
        return $query->where(function($q) {
            $q->where('booking_date', '<', now()->toDateString())
              ->orWhere(function($sq) {
                  $sq->where('booking_date', now()->toDateString())
                     ->where('end_time', '<', now()->toTimeString());
              });
        });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if booking can be cancelled
     */
    public function canBeCancelled()
    {
        // Can cancel if:
        // - Status is pending or confirmed
        // - Booking is in the future
        // - At least 24 hours before booking time
        
        if (!in_array($this->status, ['pending', 'confirmed'])) {
            return false;
        }

        $bookingDateTime = new \DateTime($this->full_date_time);
        $now = new \DateTime();
        $hoursUntilBooking = ($bookingDateTime->getTimestamp() - $now->getTimestamp()) / 3600;

        return $hoursUntilBooking >= 24;
    }

    /**
     * Cancel booking
     */
    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Confirm booking
     */
    public function confirm()
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Mark as completed
     */
    public function markCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark as no show
     */
    public function markNoShow()
    {
        $this->update(['status' => 'no_show']);
    }

    /**
     * Generate unique booking number
     */
    public static function generateBookingNumber()
    {
        do {
            $number = 'BK-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        } while (self::where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = self::generateBookingNumber();
            }
        });
    }

      protected $appends = ['title', 'description', 'final_price', 'has_active_discount', 'is_full'];
}