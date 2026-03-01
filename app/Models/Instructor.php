<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'bio_ar',
        'bio_en',
        'specialization_ar',
        'specialization_en',
        'education',
        'certifications',
        'social_links',
        'experience_years',
        'rating',
        'total_students',
        'total_courses',
        'total_books',
        'is_featured',
        'available_for_consultation',
        'consultation_price',
    ];

    protected $casts = [
        'education' => 'array',
        'certifications' => 'array',
        'social_links' => 'array',
        'rating' => 'decimal:2',
        'consultation_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'available_for_consultation' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
