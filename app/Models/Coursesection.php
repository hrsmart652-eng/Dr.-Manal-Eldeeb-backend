<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title_ar',
        'title_en',
        'description_ar',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Section belongs to a course
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Section has many lectures
     */
    public function lectures()
    {
        return $this->hasMany(Lecture::class, 'section_id')->orderBy('order');
    }

    /**
     * Get total duration of all lectures in this section
     */
    public function getTotalDurationAttribute()
    {
        return $this->lectures->sum('duration_minutes');
    }

    /**
     * Get lecture count
     */
    public function getLectureCountAttribute()
    {
        return $this->lectures->count();
    }
}