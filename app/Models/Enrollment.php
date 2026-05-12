<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use SoftDeletes;

    protected $table = 'enrollments';

    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'progress_percentage',
        'completed_lectures',
        'payment_status',
        'price_paid',
        'payment_method',
        'transaction_id',
        'enrolled_at',
        'last_accessed_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'progress_percentage' => 'integer',
        'completed_lectures' => 'integer',
        'price_paid' => 'decimal:2',
        'enrolled_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the course
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Check if enrollment is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if enrollment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get status text in Arabic
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'expired' => 'انتهت الصلاحية',
            'cancelled' => 'ملغى',
            default => $this->status,
        };
    }

    /**
     * Get payment status text in Arabic
     */
    public function getPaymentStatusTextAttribute(): string
    {
        return match($this->payment_status) {
            'completed' => 'مكتمل',
            'pending' => 'قيد الانتظار',
            'failed' => 'فشل',
            'refunded' => 'مسترجع',
            default => $this->payment_status,
        };
    }
}