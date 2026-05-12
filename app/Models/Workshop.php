<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Workshop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'instructor_id',
        'title_ar',
        'title_en',
        'slug',
        'description_ar',
        'description_en',
        'objectives',
        'prerequisites',
        'thumbnail',
        'level',
        'language',
        'type',
        'location',
        'meeting_link',
        'meeting_instructions',
        'duration_hours',
        'sessions_count',
        'start_date',
        'end_date',
        'max_attendees',
        'registered_count',
        'min_attendees',
        'price',
        'discount_price',
        'is_free',
        'early_bird_deadline',
        'early_bird_price',
        'materials',
        'certificate_provided',
        'certificate_template',
        'status',
        'published_at',
        'rating',
        'total_reviews',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'early_bird_deadline' => 'date',
        'published_at' => 'datetime',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'early_bird_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_free' => 'boolean',
        'certificate_provided' => 'boolean',
        'materials' => 'array',
        'duration_hours' => 'integer',
        'sessions_count' => 'integer',
        'max_attendees' => 'integer',
        'registered_count' => 'integer',
        'min_attendees' => 'integer',
        'total_reviews' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function sessions()
    {
        return $this->hasMany(WorkshopSession::class)->orderBy('session_number');
    }

    public function registrations()
    {
        return $this->hasMany(WorkshopRegistration::class);
    }

    public function confirmedRegistrations()
    {
        return $this->registrations()->where('status', 'confirmed');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePublished(Builder $query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming(Builder $query)
    {
        return $query->where('status', 'published')
            ->where('start_date', '>=', now());
    }

    public function scopeOngoing(Builder $query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType(Builder $query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByLevel(Builder $query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeSearch(Builder $query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title_ar', 'like', "%{$search}%")
              ->orWhere('title_en', 'like', "%{$search}%")
              ->orWhere('description_ar', 'like', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTitleAttribute()
    {
        return $this->title_ar;
    }

    public function getDescriptionAttribute()
    {
        return $this->description_ar;
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
    }

    public function getFinalPriceAttribute()
    {
        if ($this->is_free) {
            return 0;
        }

        // Check early bird
        if ($this->early_bird_deadline && 
            $this->early_bird_price && 
            now()->lte($this->early_bird_deadline)) {
            return $this->early_bird_price;
        }

        // Check discount
        if ($this->discount_price) {
            return $this->discount_price;
        }

        return $this->price;
    }

    public function getIsFullAttribute()
    {
       return $this->registered_participants >= $this->max_participants;
    }

    public function getAvailableSpotsAttribute()
    {
        return max(0, $this->max_attendees - $this->registered_count);
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'draft' => 'مسودة',
            'published' => 'منشور',
            'ongoing' => 'جاري',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }

    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'online' => 'عبر الإنترنت',
            'in_person' => 'حضوري',
            'hybrid' => 'مختلط',
            default => $this->type,
        };
    }

    public function getLevelTextAttribute()
    {
        return match($this->level) {
            'beginner' => 'مبتدئ',
            'intermediate' => 'متوسط',
            'advanced' => 'متقدم',
            'all' => 'جميع المستويات',
            default => $this->level,
        };
    }

    public function getIsUpcomingAttribute()
    {
        return $this->start_date && $this->start_date->isFuture();
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed' || 
               ($this->end_date && $this->end_date->isPast());
    }

    public function getCanRegisterAttribute()
    {
        return $this->status === 'published' 
            && !$this->is_full 
            && $this->is_upcoming;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user is registered
     */
    public function isRegisteredBy($user)
    {
        if (!$user) return false;

        return $this->registrations()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed', 'attended'])
            ->exists();
    }

    /**
     * Get registration for user
     */
    public function registrationFor($user)
    {
        if (!$user) return null;

        return $this->registrations()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Increment registered count
     */
    public function incrementRegistrations()
    {
        $this->increment('registered_participants');
    }

    /**
     * Decrement registered count
     */
    public function decrementRegistrations()
    {
        $this->decrement('registered_participants');
    }

    /**
     * Update rating
     */
    public function updateRating()
    {
        $this->rating = $this->reviews()->avg('rating') ?? 0;
        $this->total_reviews = $this->reviews()->count();
        $this->save();
    }

    /**
     * Check if workshop can start
     */
    public function canStart()
    {
        return $this->registered_count >= $this->min_attendees;
    }

    /**
     * Publish workshop
     */
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Cancel workshop
     */
    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark as completed
     */
    public function markCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Generate unique slug
     */
    public static function generateSlug($title)
    {
        $slug = \Str::slug($title);
        $count = 1;
        
        while (self::where('slug', $slug)->exists()) {
            $slug = \Str::slug($title) . '-' . $count;
            $count++;
        }
        
        return $slug;
    }
    

    /*
    |--------------------------------------------------------------------------
    | Boot Method
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workshop) {
            if (empty($workshop->slug)) {
                $workshop->slug = self::generateSlug($workshop->title_ar);
            }
        });

        static::updating(function ($workshop) {
            if ($workshop->isDirty('title_ar') && !$workshop->isDirty('slug')) {
                $workshop->slug = self::generateSlug($workshop->title_ar);
            }
        });
    }
}