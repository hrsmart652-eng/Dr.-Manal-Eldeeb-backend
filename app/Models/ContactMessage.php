<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslation;

class ContactMessage extends Model
{
    use HasFactory,HasTranslation;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'type',
        'status',
        'ip_address',
        'user_agent',
        'replied_at',
        'replied_by',
        'admin_notes',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Message replied by user
     */
    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    /**
     * Scope: Read messages
     */
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    /**
     * Scope: Replied messages
     */
    public function scopeReplied($query)
    {
        return $query->where('status', 'replied');
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get status in Arabic
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'unread' => 'غير مقروء',
            'read' => 'مقروء',
            'replied' => 'تم الرد',
            'archived' => 'مؤرشف',
            default => $this->status,
        };
    }

    /**
     * Get type in Arabic
     */
    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'general' => 'استفسار عام',
            'support' => 'دعم فني',
            'suggestion' => 'اقتراح',
            'complaint' => 'شكوى',
            'other' => 'أخرى',
            default => $this->type,
        };
    }

    /**
     * Check if replied
     */
    public function getIsRepliedAttribute()
    {
        return $this->status === 'replied' && !is_null($this->replied_at);
    }

    /**
     * Check if unread
     */
    public function getIsUnreadAttribute()
    {
        return $this->status === 'unread';
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        $this->update(['status' => 'read']);
    }

    /**
     * Mark as replied
     */
    public function markAsReplied($userId = null)
    {
        $this->update([
            'status' => 'replied',
            'replied_at' => now(),
            'replied_by' => $userId,
        ]);
    }

    /**
     * Archive message
     */
    public function archive()
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('Y-m-d H:i');
    }

    /**
     * Get short message
     */
    public function getShortMessageAttribute()
    {
        return \Str::limit($this->message, 100);
    }
       protected $appends = ['title', 'description', 'final_price', 'has_active_discount', 'is_full'];
}