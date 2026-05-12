<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WorkshopRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'user_id',
        'workshop_id',
        'price_paid',
        'payment_method',
        'payment_status',
        'status',
        'sessions_attended',
        'attendance_record',
        'certificate_issued',
        'certificate_number',
        'certificate_issued_at',
        'cancellation_reason',
        'cancelled_at',
        'notes',
        'metadata',
        // 'registered_at',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'sessions_attended' => 'integer',
        'attendance_record' => 'array',
        'certificate_issued' => 'boolean',
        'certificate_issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'registered_at' => 'datetime',
        'metadata' => 'array',
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

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending(Builder $query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed(Builder $query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeAttended(Builder $query)
    {
        return $query->where('status', 'attended');
    }

    public function scopeCancelled(Builder $query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePaymentCompleted(Builder $query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopePaymentPending(Builder $query)
    {
        return $query->where('payment_status', 'pending');
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
            'confirmed' => 'مؤكد',
            'attended' => 'حضر',
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

    public function getIsConfirmedAttribute()
    {
        return $this->status === 'confirmed';
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === 'cancelled';
    }

    public function getCanBeCancelledAttribute()
    {
        // Can cancel if confirmed and workshop hasn't started
        return $this->status === 'confirmed' 
            && $this->workshop 
            && $this->workshop->start_date 
            && $this->workshop->start_date->isFuture();
    }

    public function getAttendancePercentageAttribute()
    {
        if (!$this->workshop || $this->workshop->sessions_count == 0) {
            return 0;
        }

        return round(($this->sessions_attended / $this->workshop->sessions_count) * 100);
    }

    public function getIsEligibleForCertificateAttribute()
    {
        // Must attend at least 80% of sessions
        return $this->attendance_percentage >= 80 
            && $this->workshop 
            && $this->workshop->certificate_provided;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Confirm registration
     */
    public function confirm()
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Cancel registration
     */
    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        // Decrement workshop count
        if ($this->workshop) {
            $this->workshop->decrementRegistrations();
        }
    }

    /**
     * Mark session as attended
     */
    public function markSessionAttended($sessionId)
    {
        $record = $this->attendance_record ?? [];
        
        if (!in_array($sessionId, $record)) {
            $record[] = $sessionId;
            $this->update([
                'attendance_record' => $record,
                'sessions_attended' => count($record),
            ]);
        }
    }

    /**
     * Issue certificate
     */
    public function issueCertificate()
    {
        if (!$this->is_eligible_for_certificate) {
            return false;
        }

        if ($this->certificate_issued) {
            return true; // Already issued
        }

        $certificateNumber = $this->generateCertificateNumber();

        $this->update([
            'certificate_issued' => true,
            'certificate_number' => $certificateNumber,
            'certificate_issued_at' => now(),
        ]);

        return true;
    }

    /**
     * Generate certificate number
     */
    protected function generateCertificateNumber()
    {
        do {
            $number = 'CERT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        } while (self::where('certificate_number', $number)->exists());

        return $number;
    }

    /**
     * Generate registration number
     */
    public static function generateNumber()
    {
        do {
            $number = 'WR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        } while (self::where('registration_number', $number)->exists());

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Method
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($registration) {
            // Auto-generate registration number
            if (empty($registration->registration_number)) {
                $registration->registration_number = self::generateNumber();
            }

            // Set registered_at timestamp
            if (empty($registration->registered_at)) {
                // $registration->registered_at = now();
            }

            // Increment workshop count if confirmed
            if ($registration->status === 'confirmed') {
                $workshop = Workshop::find($registration->workshop_id);
                if ($workshop) {
                    $workshop->incrementRegistrations();
                }
            }
        });

        static::updated(function ($registration) {
            // Handle status changes
            if ($registration->isDirty('status')) {
                $oldStatus = $registration->getOriginal('status');
                $newStatus = $registration->status;

                // If changing from confirmed to cancelled
                if ($oldStatus === 'confirmed' && $newStatus === 'cancelled') {
                    if ($registration->workshop) {
                        $registration->workshop->decrementRegistrations();
                    }
                }

                // If changing from pending/cancelled to confirmed
                if (in_array($oldStatus, ['pending', 'cancelled']) && $newStatus === 'confirmed') {
                    if ($registration->workshop) {
                        $registration->workshop->incrementRegistrations();
                    }
                }
            }
        });
    }
}
