<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasTranslation;

class Category extends Model
{
    use HasFactory, SoftDeletes, HasTranslation;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'type',
        'parent_id',
        'icon',
        'image',
        'color',
        'order',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Category has many courses
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Category has many books
     */
    public function books()
    {
        return $this->hasMany(Book::class);
    }

    /**
     * Category can have a parent
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Category can have children
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

    /**
     * Active children
     */
    public function activeChildren()
    {
        return $this->children()->where('is_active', true);
    }
    /**
 * Bringing children and children's children to infinity
 */
public function allChildren()
{
    // to bring all descendants
    return $this->activeChildren()->with('allChildren');
}

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active categories
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Root categories (no parent)
     */
    public function scopeRoot(Builder $query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: By type
     */
    public function scopeByType(Builder $query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: With counts
     */
    public function scopeWithCounts(Builder $query)
    {
        return $query->withCount(['courses', 'books']);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get full image URL
     */
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * Get icon URL or class
     */
    public function getIconUrlAttribute()
    {
        if (!$this->icon) {
            return null;
        }

        // If it's a file path
        if (str_contains($this->icon, '/') || str_contains($this->icon, '.')) {
            return asset('storage/' . $this->icon);
        }

        // Otherwise it's an icon class (e.g., "fas fa-book")
        return $this->icon;
    }

    /**
     * Get type in Arabic
     */
    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'course' => 'دورات',
            'book' => 'كتب',
            'workshop' => 'ورش عمل',
            'general' => 'عام',
            default => $this->type,
        };
    }

    /**
     * Check if has children
     */
    public function getHasChildrenAttribute()
    {
        return $this->children()->exists();
    }

    /**
     * Get breadcrumb path
     */
    public function getBreadcrumbAttribute()
    {
        $breadcrumb = [];
        $category = $this;

        while ($category) {
            array_unshift($breadcrumb, [
                'id' => $category->id,
                'name' => $category->name_ar,
                'slug' => $category->slug,
            ]);
            $category = $category->parent;
        }

        return $breadcrumb;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get all descendant IDs (including this category)
     */
    public function getDescendantIds()
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getDescendantIds());
        }

        return $ids;
    }

    /**
     * Get total items count (courses + books)
     */
    public function getTotalItemsCount()
    {
        return $this->courses()->count() + $this->books()->count();
    }

    /**
     * Check if category is empty
     */
    public function isEmpty()
    {
        return $this->getTotalItemsCount() === 0;
    }

    /**
     * Update order
     */
    public function updateOrder($newOrder)
    {
        $this->update(['order' => $newOrder]);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug on creating
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = \Str::slug($category->name_ar);
            }

            // Auto-set order if not set
            if (is_null($category->order)) {
                $maxOrder = static::where('parent_id', $category->parent_id)
                    ->max('order');
                $category->order = $maxOrder ? $maxOrder + 1 : 0;
            }
        });

        // Update slug if name changes
        static::updating(function ($category) {
            if ($category->isDirty('name_ar') && !$category->isDirty('slug')) {
                $category->slug = \Str::slug($category->name_ar);
            }
        });
    }
       protected $appends = ['title', 'description',];
}