<?php


namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'type',
        'avatar',
        'bio',
        'birth_date',
        'gender',
        'country',
        'city',
        'is_active',
        'verification_code',
        'code_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'code_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // ========== ROLE CHECKS ==========
    public function isStudent(): bool
    {
        return $this->type === 'student';
    }

    public function isInstructor(): bool
    {
        return $this->type === 'instructor';
    }

    public function isAdmin(): bool
    {
        return $this->type === 'admin';
    }

    // ========== STATUS CHECKS ==========
    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    // ========== RELATIONSHIPS ==========
    
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'enrollments',
            'user_id',
            'course_id'
        )->withPivot(
            'id',
            'status',
            'progress_percentage',
            'completed_lectures',
            'payment_status',
            'price_paid',
            'payment_method',
            'transaction_id',
            // 'enrolled_at',
            'last_accessed_at',
            'completed_at',
            'expires_at'
        )->withTimestamps();
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'student_id');
    }

    public function assignedConsultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'instructor_id');
    }

    public function enrollments()
{
    return $this->hasMany(\App\Models\Enrollment::class);
}


// داخل كلاس User
public function instructor()
{
    // اختر نوع العلاقة المناسب (hasOne, belongsTo, etc.)
    return $this->hasOne(Instructor::class); 
}
// داخل ملف app/Models/User.php

public function workshopRegistrations()
{
    
    return $this->hasMany(WorkshopRegistration::class);
}


/**
 *Book Purchases relationship
 */
public function bookPurchases()
{
    return $this->hasMany(\App\Models\BookPurchase::class);
}

/**\ensure user has purchased a specific book
 */
public function hasPurchasedBook($bookId)
{
    return $this->bookPurchases()->where('book_id', $bookId)
                ->where('payment_status', 'completed')
                ->exists();
}
}
// namespace App\Models;

// use Database\Factories\UserFactory;
// use Illuminate\Database\Eloquent\Attributes\Fillable;
// use Illuminate\Database\Eloquent\Attributes\Hidden;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;
// use Laravel\Passport\HasApiTokens;  // ✅ ADD THIS

// #[Fillable([
//     'name',
//     'email',
//     'password',
//     'phone',
//     'type',
//     'avatar',
//     'bio',
//     'birth_date',
//     'gender',
//     'country',
//     'city',
//     'is_active',
//     'verification_code',
//     'code_expires_at',
// ])]
// #[Hidden(['password', 'remember_token', 'verification_code'])]
// class User extends Authenticatable
// {
//     use HasFactory, Notifiable, SoftDeletes, HasApiTokens;  // ✅ ADD HasApiTokens

//     protected function casts(): array
//     {
//         return [
//             'email_verified_at' => 'datetime',
//             'password' => 'hashed',
//             'birth_date' => 'date',
//             'code_expires_at' => 'datetime',
//             'is_active' => 'boolean',
//         ];
//     }

//     // ========== ROLE CHECKS ==========
//     public function isStudent(): bool
//     {
//         return $this->type === 'student';
//     }

//     public function isInstructor(): bool
//     {
//         return $this->type === 'instructor';
//     }

//     public function isAdmin(): bool
//     {
//         return $this->type === 'admin';
//     }

//     // ========== STATUS CHECKS ==========
//     public function isEmailVerified(): bool
//     {
//         return $this->email_verified_at !== null;
//     }

//     public function isActive(): bool
//     {
//         return $this->is_active === true;
//     }

//     // ========== RELATIONSHIPS ==========
    
//     /**
//      * Get courses taught by instructor
//      */
//     public function courses(): HasMany
//     {
//         return $this->hasMany(Course::class, 'instructor_id');
//     }

//     /**
//      * Get enrolled courses for student
//      * Using 'enrollments' table
//      */
//     public function enrolledCourses(): BelongsToMany
//     {
//         return $this->belongsToMany(
//             Course::class,
//             'enrollments',
//             'user_id',
//             'course_id'
//         )->withPivot(
//             'id',
//             'status',
//             'progress_percentage',
//             'completed_lectures',
//             'payment_status',
//             'price_paid',
//             'payment_method',
//             'transaction_id',
//             'enrolled_at',
//             'last_accessed_at',
//             'completed_at',
//             'expires_at'
//         )->withTimestamps();
//     }

//     /**
//      * Get consultations created by student
//      */
//     public function consultations(): HasMany
//     {
//         return $this->hasMany(Consultation::class, 'student_id');
//     }

//     /**
//      * Get consultations assigned to instructor
//      */
//     public function assignedConsultations(): HasMany
//     {
//         return $this->hasMany(Consultation::class, 'instructor_id');
//     }
// }

// namespace App\Models;

// // use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;
// use Laravel\Passport\HasApiTokens;

// class User extends Authenticatable
// {
//     /** @use HasFactory<\Database\Factories\UserFactory> */
//     use HasFactory, Notifiable,HasApiTokens;

//     /**
//      * The attributes that are mass assignable.
//      *
//      * @var list<string>
//      */
//     protected $fillable = [
//         'name',
//         'email',
//         'password',
//     ];

//     /**
//      * The attributes that should be hidden for serialization.
//      *
//      * @var list<string>
//      */
//     protected $hidden = [
//         'password',
//         'remember_token',
//     ];

//     /**
//      * Get the attributes that should be cast.
//      *
//      * @return array<string, string>
//      */
//     protected function casts(): array
//     {
//         return [
//             'email_verified_at' => 'datetime',
//             'password' => 'hashed',
//         ];
//     }
// }
