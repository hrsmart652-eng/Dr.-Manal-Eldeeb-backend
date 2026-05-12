<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'date',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Blocked slot belongs to an instructor
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Check if this is a full day block
     */
    public function isFullDayBlock()
    {
        return is_null($this->start_time) && is_null($this->end_time);
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->date->format('Y-m-d');
    }

    /**
     * Get time range or full day
     */
    public function getTimeRangeAttribute()
    {
        if ($this->isFullDayBlock()) {
            return 'اليوم كامل';
        }

        return $this->start_time . ' - ' . $this->end_time;
    }
}