<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WorkshopSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'workshop_id',
        'title_ar',
        'title_en',
        'description_ar',
        'session_number',
        'session_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'location',
        'meeting_link',
        'materials',
        'recording_url',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'completed_at' => 'datetime',
        'materials' => 'array',
        'session_number' => 'integer',
        'duration_minutes' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeScheduled(Builder $query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming(Builder $query)
    {
        return $query->where('status', 'scheduled')
            ->where('session_date', '>=', now()->toDateString());
    }

    public function scopePast(Builder $query)
    {
        return $query->where('session_date', '<', now()->toDateString());
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTitleAttribute()
    {
        return $this->title_ar ?? "جلسة {$this->session_number}";
    }

    public function getDescriptionAttribute()
    {
        return $this->description_ar;
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'scheduled' => 'مجدولة',
            'ongoing' => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغية',
            default => $this->status,
        };
    }

    public function getIsUpcomingAttribute()
    {
        return $this->session_date && $this->session_date->isFuture();
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getFormattedDateAttribute()
    {
        return $this->session_date ? $this->session_date->format('Y-m-d') : null;
    }

    public function getFormattedTimeAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        return date('H:i', strtotime($this->start_time)) . ' - ' . 
               date('H:i', strtotime($this->end_time));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark session as completed
     */
    public function markCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancel session
     */
    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Calculate duration in minutes
     */
    public function calculateDuration()
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        
        return round(($end - $start) / 60);
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Method
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            // Auto-calculate duration if not set
            if (empty($session->duration_minutes) && $session->start_time && $session->end_time) {
                $session->duration_minutes = $session->calculateDuration();
            }
        });

        static::updating(function ($session) {
            // Update duration if times changed
            if (($session->isDirty('start_time') || $session->isDirty('end_time')) 
                && $session->start_time && $session->end_time) {
                $session->duration_minutes = $session->calculateDuration();
            }
        });
    }
}