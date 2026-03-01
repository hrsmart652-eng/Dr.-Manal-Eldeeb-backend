<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LectureProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lecture_id',
        'enrollment_id',
        'is_completed',
        'watch_time_seconds',
        'total_duration_seconds',
        'completion_percentage',
        'completed_at',
        'last_watched_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'watch_time_seconds' => 'integer',
        'total_duration_seconds' => 'integer',
        'completion_percentage' => 'integer',
        'completed_at' => 'datetime',
        'last_watched_at' => 'datetime',
    ];

    /**
     * Progress belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Progress belongs to a lecture
     */
    public function lecture()
    {
        return $this->belongsTo(Lecture::class);
    }

    /**
     * Progress belongs to an enrollment
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Mark as completed
     */
    public function markCompleted()
    {
        $this->update([
            'is_completed' => true,
            'completion_percentage' => 100,
            'completed_at' => now(),
        ]);

        // Update enrollment progress
        $this->enrollment->updateProgress();
    }

    /**
     * Update watch time
     */
    public function updateWatchTime($seconds)
    {
        $this->update([
            'watch_time_seconds' => $seconds,
            'last_watched_at' => now(),
            'completion_percentage' => $this->calculateCompletionPercentage($seconds),
        ]);

        // Auto-complete if watched enough
        if ($this->completion_percentage >= 90 && !$this->is_completed) {
            $this->markCompleted();
        }
    }

    /**
     * Calculate completion percentage
     */
    private function calculateCompletionPercentage($watchedSeconds)
    {
        if ($this->total_duration_seconds === 0) {
            return 0;
        }

        return min(100, round(($watchedSeconds / $this->total_duration_seconds) * 100));
    }
}
