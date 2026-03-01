<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'price_paid',
        'payment_method',
        'transaction_id',
        'payment_status',
        'status',
        'progress_percentage',
        'completed_lectures',
        'last_accessed_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'progress_percentage' => 'integer',
        'completed_lectures' => 'integer',
        'last_accessed_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Enrollment belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enrollment belongs to a course
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Enrollment has many lecture progress
     */
    public function lectureProgress()
    {
        return $this->hasMany(LectureProgress::class);
    }

    /**
     * Get payment status in Arabic
     */
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

    /**
     * Get enrollment status in Arabic
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'expired' => 'منتهي',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }

    /**
     * Check if enrollment is active
     */
    public function isActive()
    {
        return $this->status === 'active' 
            && $this->payment_status === 'completed'
            && (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if enrollment is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed' || $this->progress_percentage >= 100;
    }

    /**
     * Update progress
     */
    public function updateProgress()
    {
        $course = $this->course;
        $totalLectures = $course->total_lectures;
        
        if ($totalLectures === 0) {
            return;
        }

        $completedLectures = $this->lectureProgress()
            ->where('is_completed', true)
            ->count();

        $progressPercentage = round(($completedLectures / $totalLectures) * 100);

        $this->update([
            'completed_lectures' => $completedLectures,
            'progress_percentage' => $progressPercentage,
            'status' => $progressPercentage >= 100 ? 'completed' : 'active',
            'completed_at' => $progressPercentage >= 100 ? now() : null,
        ]);
    }

    /**
     * Touch last accessed
     */
    public function touchLastAccessed()
    {
        $this->update(['last_accessed_at' => now()]);
    }
}