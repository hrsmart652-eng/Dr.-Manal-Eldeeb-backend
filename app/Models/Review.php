<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslation;

class Review extends Model
{
    use HasFactory,HasTranslation;

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'pros',
        'cons',
        'is_approved',
        'is_verified_purchase',
        'approved_by',
        'approved_at',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'pros' => 'array',
        'cons' => 'array',
        'is_approved' => 'boolean',
        'is_verified_purchase' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function reviewable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
       protected $appends = ['title', 'description', 'final_price', 'has_active_discount', 'is_full'];
}