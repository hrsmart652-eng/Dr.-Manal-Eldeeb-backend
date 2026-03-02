<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'format',
        'price_paid',
        'payment_method',
        'transaction_id',
        'payment_status',
        'download_link',
        'download_count',
        'max_downloads',
        'shipping_address',
        'tracking_number',
        'shipping_status',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'download_count' => 'integer',
        'max_downloads' => 'integer',
    ];

    /**
     * Purchase belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Purchase belongs to a book
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get payment status in Arabic
     */
    public function getPaymentStatusTextAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'قيد الانتظار',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            'refunded' => 'مسترد',
            default => $this->payment_status,
        };
    }

    /**
     * Get shipping status in Arabic
     */
    public function getShippingStatusTextAttribute()
    {
        return match($this->shipping_status) {
            'pending' => 'قيد التجهيز',
            'shipped' => 'تم الشحن',
            'delivered' => 'تم التوصيل',
            default => $this->shipping_status,
        };
    }

    /**
     * Check if purchase is digital
     */
    public function isDigital()
    {
        return in_array($this->format, ['digital', 'both']);
    }

    /**
     * Check if purchase is physical
     */
    public function isPhysical()
    {
        return in_array($this->format, ['physical', 'both']);
    }

    /**
     * Check if can download
     */
    public function canDownload()
    {
        return $this->isDigital() 
            && $this->payment_status === 'completed'
            && $this->download_count < $this->max_downloads;
    }

    /**
     * Increment download count
     */
    public function incrementDownloads()
    {
        $this->increment('download_count');
    }

    /**
     * Generate download link
     */
    public function generateDownloadLink()
    {
        if (!$this->canDownload()) {
            return null;
        }

        $token = \Str::random(64);
        $expiry = now()->addMinutes(30);

        $this->update([
            'download_link' => encrypt([
                'token' => $token,
                'expires_at' => $expiry,
                'book_id' => $this->book_id,
                'user_id' => $this->user_id,
            ]),
        ]);

        return route('books.download', ['token' => $token]);
    }
}
