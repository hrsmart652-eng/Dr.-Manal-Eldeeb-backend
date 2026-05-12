<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'instructor_id',
        'title',
        'title_ar',
        'slug',
        'description',
        'description_ar',
        'thumbnail',
        'price',
        'level',
        'level_text',
        'status',
        'total_lectures',
        'total_sections',
        'duration_hours',
        'total_duration',
        'enrolled_count',
        'rating',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'requirements' => 'array',
         'what_you_learn' => 'array',

    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->slug) {
                $model->slug = Str::slug($model->title) . '-' . uniqid();
            }
        });
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'enrollments',
            'course_id',
            'user_id'
        )->withPivot(
            'id',
            'status',
            'progress_percentage',
            'completed_lectures',
            'payment_status',
            'price_paid',
            'payment_method',
            'transaction_id',
            'last_accessed_at',
            'completed_at',
            'expires_at'
        )->withTimestamps();
    }

    /**
     * Get books through course_book pivot table
     */
 public function books()
{
    return $this->belongsToMany(Book::class, 'course_book');
}

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = intdiv($this->total_duration ?? 0, 60);
        $minutes = ($this->total_duration ?? 0) % 60;
        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        }
        return "{$minutes}m";
    }
}