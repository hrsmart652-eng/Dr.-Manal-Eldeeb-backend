<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'slug',
        'category_id',
        'instructor_id',
        'thumbnail',
        'video_intro',
        'video_provider',
        'price',
        'discount_price',
        'discount_percentage',
        'discount_ends_at',
        'level',
        'status',
        'type',
        'duration_hours',
        'total_lectures',
        'enrolled_students',
        'max_students',
        'rating',
        'total_reviews',
        'requirements',
        'what_you_learn',
        'tags',
        'is_featured',
        'has_certificate',
        'certificate_template',
        'published_at',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'requirements' => 'array',
        'what_you_learn' => 'array',
        'tags' => 'array',
        'is_featured' => 'boolean',
        'has_certificate' => 'boolean',
        'published_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'discount_ends_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Course belongs to a category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Course belongs to an instructor
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Course has many sections
     */
    public function sections()
    {
        return $this->hasMany(CourseSection::class)->orderBy('order');
    }

    /**
     * Course has many enrollments
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Course has many reviews
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Course approved reviews only
     */
    public function approvedReviews()
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Users enrolled in this course
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot('status', 'progress_percentage', 'enrolled_at')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get final price (considering discount)
     */
    public function getFinalPriceAttribute()
    {
        if ($this->discount_price && $this->discount_ends_at && $this->discount_ends_at->isFuture()) {
            return $this->discount_price;
        }
        return $this->price;
    }

    /**
     * Check if course has active discount
     */
    public function getHasActiveDiscountAttribute()
    {
        return $this->discount_price 
            && $this->discount_ends_at 
            && $this->discount_ends_at->isFuture();
    }

    /**
     * Get level in Arabic
     */
    public function getLevelTextAttribute()
    {
        return match($this->level) {
            'beginner' => 'مبتدئ',
            'intermediate' => 'متوسط',
            'advanced' => 'متقدم',
            default => $this->level,
        };
    }

    /**
     * Get status in Arabic
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'draft' => 'مسودة',
            'published' => 'منشور',
            'archived' => 'مؤرشف',
            default => $this->status,
        };
    }

    /**
     * Check if course is full
     */
    public function getIsFullAttribute()
    {
        if (!$this->max_students) {
            return false;
        }
        return $this->enrolled_students >= $this->max_students;
    }

    /**
     * Get available seats
     */
    public function getAvailableSeatsAttribute()
    {
        if (!$this->max_students) {
            return null;
        }
        return max(0, $this->max_students - $this->enrolled_students);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Published courses only
     */
    public function scopePublished(Builder $query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope: Featured courses
     */
    public function scopeFeatured(Builder $query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory(Builder $query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: By instructor
     */
    public function scopeByInstructor(Builder $query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope: By level
     */
    public function scopeByLevel(Builder $query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope: Free courses
     */
    public function scopeFree(Builder $query)
    {
        return $query->where('price', 0);
    }

    /**
     * Scope: Paid courses
     */
    public function scopePaid(Builder $query)
    {
        return $query->where('price', '>', 0);
    }

    /**
     * Scope: Search by title or description
     */
    public function scopeSearch(Builder $query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title_ar', 'like', "%{$search}%")
              ->orWhere('title_en', 'like', "%{$search}%")
              ->orWhere('description_ar', 'like', "%{$search}%")
              ->orWhere('description_en', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: Popular courses (most enrolled)
     */
    public function scopePopular(Builder $query)
    {
        return $query->orderBy('enrolled_students', 'desc');
    }

    /**
     * Scope: Highest rated
     */
    public function scopeTopRated(Builder $query)
    {
        return $query->orderBy('rating', 'desc');
    }

    /**
     * Scope: Recently published
     */
    public function scopeRecent(Builder $query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user is enrolled
     */
    public function isEnrolledBy(User $user)
    {
        return $this->enrollments()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get enrollment for user
     */
    public function enrollmentFor(User $user)
    {
        return $this->enrollments()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Increment enrolled students count
     */
    public function incrementEnrollments()
    {
        $this->increment('enrolled_students');
    }

    /**
     * Decrement enrolled students count
     */
    public function decrementEnrollments()
    {
        $this->decrement('enrolled_students');
    }

    /**
     * Update course rating
     */
    public function updateRating()
    {
        $approved = $this->approvedReviews();
        
        $this->update([
            'rating' => $approved->avg('rating') ?? 0,
            'total_reviews' => $approved->count(),
        ]);
    }

    /**
     * Check if course can be enrolled
     */
    public function canEnroll()
    {
        return $this->status === 'published' 
            && !$this->is_full 
            && ($this->starts_at === null || $this->starts_at->isFuture() || $this->starts_at->isPast());
    }

    /**
     * Get total duration in minutes
     */
    public function getTotalDurationMinutes()
    {
        return $this->sections()
            ->with('lectures')
            ->get()
            ->sum(function($section) {
                return $section->lectures->sum('duration_minutes');
            });
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug on creating
        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = \Str::slug($course->title_ar);
            }
        });
    }
}