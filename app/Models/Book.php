<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'slug',
        'category_id',
        'author_id',
        'isbn',
        'cover_image',
        'format',
        'digital_price',
        'physical_price',
        'discount_price',
        'publisher',
        'publication_date',
        'pages',
        'language',
        'status',
        'file_path',
        'file_size_mb',
        'total_sales',
        'stock_quantity',
        'rating',
        'total_reviews',
        'sample_pages',
        'is_featured',
        'published_at',
    ];

    protected $casts = [
        'digital_price' => 'decimal:2',
        'physical_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'publication_date' => 'date',
        'sample_pages' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
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

    public function author()
    {
        return $this->belongsTo(Instructor::class, 'author_id');
    }

    public function purchases()
    {
        return $this->hasMany(BookPurchase::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function approvedReviews()
    {
        return $this->reviews()->where('is_approved', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    public function getFinalPriceAttribute()
    {
        if ($this->discount_price) {
            return $this->discount_price;
        }
        return $this->digital_price;
    }

    public function getHasDiscountAttribute()
    {
        return !is_null($this->discount_price) && $this->discount_price < $this->digital_price;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->has_discount || $this->digital_price == 0) {
            return 0;
        }
        return round((($this->digital_price - $this->discount_price) / $this->digital_price) * 100);
    }

    public function getIsDigitalAttribute()
    {
        return in_array($this->format, ['pdf', 'epub', 'both']);
    }

    public function getIsPhysicalAttribute()
    {
        return in_array($this->format, ['physical', 'both']);
    }

    public function getInStockAttribute()
    {
        if (!$this->is_physical) {
            return true; // Digital always in stock
        }
        return $this->stock_quantity > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePublished(Builder $query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory(Builder $query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByAuthor(Builder $query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    public function scopeDigital(Builder $query)
    {
        return $query->whereIn('format', ['pdf', 'epub', 'both']);
    }

    public function scopePhysical(Builder $query)
    {
        return $query->whereIn('format', ['physical', 'both']);
    }

    public function scopeInStock(Builder $query)
    {
        return $query->where(function($q) {
            $q->whereIn('format', ['pdf', 'epub'])
              ->orWhere(function($sq) {
                  $sq->whereIn('format', ['physical', 'both'])
                     ->where('stock_quantity', '>', 0);
              });
        });
    }

    public function scopeSearch(Builder $query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title_ar', 'like', "%{$search}%")
              ->orWhere('title_en', 'like', "%{$search}%")
              ->orWhere('description_ar', 'like', "%{$search}%")
              ->orWhere('isbn', 'like', "%{$search}%")
              ->orWhere('publisher', 'like', "%{$search}%");
        });
    }

    public function scopePopular(Builder $query)
    {
        return $query->orderBy('total_sales', 'desc');
    }

    public function scopeTopRated(Builder $query)
    {
        return $query->orderBy('rating', 'desc');
    }

    public function scopeRecent(Builder $query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isPurchasedBy(User $user)
    {
        return $this->purchases()
            ->where('user_id', $user->id)
            ->where('payment_status', 'completed')
            ->exists();
    }

    public function purchaseFor(User $user)
    {
        return $this->purchases()
            ->where('user_id', $user->id)
            ->where('payment_status', 'completed')
            ->first();
    }

    public function incrementSales()
    {
        $this->increment('total_sales');
    }

    public function decrementStock($quantity = 1)
    {
        if ($this->is_physical) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function updateRating()
    {
        $approved = $this->approvedReviews();
        
        $this->update([
            'rating' => $approved->avg('rating') ?? 0,
            'total_reviews' => $approved->count(),
        ]);
    }

    public function canPurchase()
    {
        return $this->status === 'published' 
            && $this->in_stock;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($book) {
            if (empty($book->slug)) {
                $book->slug = \Str::slug($book->title_ar);
            }
        });
    }
}