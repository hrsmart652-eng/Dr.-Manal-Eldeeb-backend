<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslation;

class Instructor extends Model
{
    use HasFactory,HasTranslation;

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
        'experience_years' => 'integer',
        'rating' => 'decimal:2',
        'total_students' => 'integer',
        'total_courses' => 'integer',
        'total_books' => 'integer',
        'is_featured' => 'boolean',
        'available_for_consultation' => 'boolean',
        'consultation_price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function books()
    {
        return $this->hasMany(Book::class, 'author_id');
    }

    public function availabilitySchedules()
    {
        return $this->hasMany(AvailabilitySchedule::class);
    }

    public function blockedSlots()
    {
        return $this->hasMany(BlockedSlot::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeAvailableForConsultation($query)
    {
        return $query->where('available_for_consultation', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute()
    {
        return ($this->title ? $this->title . ' ' : '') . $this->user->name;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Update instructor statistics
     */
    public function updateStatistics()
    {
        $this->update([
            'total_courses' => $this->courses()->count(),
            'total_books' => $this->books()->count(),
            'total_students' => $this->courses()->sum('enrolled_students'),
        ]);
    }

    /**
     * Update instructor rating
     */
    public function updateRating()
    {
        $coursesRating = $this->courses()->avg('rating') ?? 0;
        $booksRating = $this->books()->avg('rating') ?? 0;
        
        // Average of courses and books ratings
        $totalItems = $this->courses()->count() + $this->books()->count();
        
        if ($totalItems > 0) {
            $avgRating = (($coursesRating * $this->courses()->count()) + 
                         ($booksRating * $this->books()->count())) / $totalItems;
            
            $this->update(['rating' => round($avgRating, 2)]);
        }
    }

    /**
     * Check if instructor is available on a specific date/time
     */
    public function isAvailableAt(\DateTime $datetime)
    {
        $dayOfWeek = strtolower($datetime->format('l'));
        $time = $datetime->format('H:i:s');
        $date = $datetime->format('Y-m-d');

        // Check if there's a blocked slot for this date/time
        $isBlocked = $this->blockedSlots()
            ->where('date', $date)
            ->where(function($query) use ($time) {
                $query->whereNull('start_time') // Full day block
                    ->orWhere(function($q) use ($time) {
                        $q->where('start_time', '<=', $time)
                          ->where('end_time', '>=', $time);
                    });
            })
            ->exists();

        if ($isBlocked) {
            return false;
        }

        // Check if there's an availability schedule for this day/time
        return $this->availabilitySchedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->exists();
    }

    /**
     * Get available time slots for a date
     */
    public function getAvailableSlots($date, $duration = 60)
    {
        $datetime = new \DateTime($date);
        $dayOfWeek = strtolower($datetime->format('l'));

        // Get schedules for this day
        $schedules = $this->availabilitySchedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        $availableSlots = [];

        foreach ($schedules as $schedule) {
            $slots = $this->generateTimeSlots(
                $date,
                $schedule->start_time,
                $schedule->end_time,
                $duration
            );

            foreach ($slots as $slot) {
                // Check if slot is not blocked
                if ($this->isAvailableAt(new \DateTime($slot['start']))) {
                    // Check if slot is not already booked
                    $isBooked = $this->bookings()
                        ->where('booking_date', $date)
                        ->where('start_time', '<=', $slot['start_time'])
                        ->where('end_time', '>', $slot['start_time'])
                        ->whereIn('status', ['confirmed', 'pending'])
                        ->exists();

                    if (!$isBooked) {
                        $availableSlots[] = $slot;
                    }
                }
            }
        }

        return $availableSlots;
    }

    /**
     * Generate time slots
     */
    private function generateTimeSlots($date, $startTime, $endTime, $duration)
    {
        $slots = [];
        $start = new \DateTime("$date $startTime");
        $end = new \DateTime("$date $endTime");
        $interval = new \DateInterval("PT{$duration}M");

        while ($start < $end) {
            $slotEnd = clone $start;
            $slotEnd->add($interval);

            if ($slotEnd <= $end) {
                $slots[] = [
                    'start' => $start->format('Y-m-d H:i:s'),
                    'end' => $slotEnd->format('Y-m-d H:i:s'),
                    'start_time' => $start->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'date' => $date,
                ];
            }

            $start->add($interval);
        }

        return $slots;
    }
       protected $appends = ['title', 'description'];
}