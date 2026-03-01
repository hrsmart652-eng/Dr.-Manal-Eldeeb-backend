<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecture extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'title_ar',
        'title_en',
        'description_ar',
        'type',
        'content_url',
        'video_provider',
        'duration_minutes',
        'is_preview',
        'is_downloadable',
        'order',
        'resources',
        'available_at',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'is_preview' => 'boolean',
        'is_downloadable' => 'boolean',
        'order' => 'integer',
        'resources' => 'array',
        'available_at' => 'datetime',
    ];

    /**
     * Lecture belongs to a section
     */
    public function section()
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    /**
     * Lecture has many progress records
     */
    public function progress()
    {
        return $this->hasMany(LectureProgress::class);
    }

    /**
     * Get lecture type in Arabic
     */
    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'video' => 'فيديو',
            'article' => 'مقال',
            'quiz' => 'اختبار',
            'assignment' => 'واجب',
            'file' => 'ملف',
            'live_session' => 'جلسة مباشرة',
            default => $this->type,
        };
    }

    /**
     * Check if lecture is completed by user
     */
    public function isCompletedBy(User $user)
    {
        return $this->progress()
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->exists();
    }

    /**
     * Get progress for user
     */
    public function progressFor(User $user)
    {
        return $this->progress()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Check if lecture is available
     */
    public function isAvailable()
    {
        if (!$this->available_at) {
            return true;
        }
        return $this->available_at->isPast();
    }
}