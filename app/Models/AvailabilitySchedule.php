<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailabilitySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'slot_duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Schedule belongs to an instructor
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Get day of week in Arabic
     */
    public function getDayOfWeekTextAttribute()
    {
        return match($this->day_of_week) {
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت',
            default => $this->day_of_week,
        };
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute()
    {
        return $this->start_time . ' - ' . $this->end_time;
    }

    /**
     * Calculate number of slots
     */
    public function getTotalSlotsAttribute()
    {
        $start = new \DateTime($this->start_time);
        $end = new \DateTime($this->end_time);
        $diffInMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
        
        return floor($diffInMinutes / $this->slot_duration_minutes);
    }
}
