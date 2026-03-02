<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookPurchaseResource;
use App\Models\Book;
use App\Models\BookPurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @group Student - Books
 * 
 * APIs for student book purchases and downloads
 */
class BookController extends Controller
{
    /**
     * Get my purchased books
     * 
     * Get list of books the authenticated student has purchased.
     * 
     * @authenticated
     * 
     * @queryParam format string Filter by format (digital, physical). Example: digital
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->bookPurchases()
            ->with(['book.author.user', 'book.category'])
            ->where('payment_status', 'completed');

        // Filter by format
        if ($request->has('format')) {
            $query->where('format', $request->format());
        }

        $purchases = $query->latest()->paginate(12);

        return response()->json([
            'success' => true,
            'data' => BookPurchaseResource::collection($purchases),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    /**
     * Purchase a book
     * 
     * Purchase a book (digital or physical).
     * 
     * @authenticated
     * 
     * @urlParam bookId integer required Book ID. Example: 1
     * @bodyParam format string required Format to purchase (digital, physical, both). Example: digital
     * @bodyParam payment_method string Payment method. Example: credit_card
     * @bodyParam shipping_address text Shipping address (required for physical). Example: شارع التحرير، القاهرة
     */
    public function purchase(Request $request, int $bookId): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:digital,physical,both',
            'payment_method' => 'sometimes|string',
            'shipping_address' => 'required_if:format,physical,both|string',
        ]);

        $user = $request->user();
        $book = Book::findOrFail($bookId);

        // Check if already purchased
        $existingPurchase = $book->purchases()
            ->where('user_id', $user->id)
            ->where('payment_status', 'completed')
            ->first();

        if ($existingPurchase) {
            return response()->json([
                'success' => false,
                'message' => 'لقد قمت بشراء هذا الكتاب بالفعل',
            ], 400);
        }

        // Check if book can be purchased
        if (!$book->canPurchase()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الكتاب غير متاح للشراء حالياً',
            ], 422);
        }

        // Calculate price
        $price = $this->calculatePrice($book, $request->format());

        try {
            DB::beginTransaction();

            // For free books or when payment is completed
            $paymentStatus = $price == 0 ? 'completed' : 'pending';

            // Create purchase
            $purchase = BookPurchase::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'format' => $request->format(),
                'price_paid' => $price,
                'payment_method' => $request->payment_method ?? 'free',
                'payment_status' => $paymentStatus,
                'shipping_address' => $request->shipping_address,
                'max_downloads' => 3,
            ]);

            // If payment completed
            if ($paymentStatus === 'completed') {
                $book->incrementSales();
                
                // Decrement stock for physical
                if (in_array($request->format(), ['physical', 'both'])) {
                    $book->decrementStock();
                }

                // Generate download link for digital
                if (in_array($request->format(), ['digital', 'both'])) {
                    $purchase->generateDownloadLink();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $paymentStatus === 'completed' 
                    ? 'تم شراء الكتاب بنجاح' 
                    : 'يرجى إتمام عملية الدفع',
                'data' => new BookPurchaseResource($purchase->load('book.author.user')),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الشراء. يرجى المحاولة مرة أخرى.',
            ], 500);
        }
    }

    /**
     * Download purchased book
     * 
     * Download a purchased digital book.
     * 
     * @authenticated
     * 
     * @urlParam bookId integer required Book ID. Example: 1
     */
    public function download(Request $request, int $bookId): JsonResponse
    {
        $user = $request->user();
        $book = Book::findOrFail($bookId);

        // Check if purchased
        $purchase = $book->purchases()
            ->where('user_id', $user->id)
            ->where('payment_status', 'completed')
            ->first();

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'يجب شراء الكتاب أولاً',
            ], 403);
        }

        // Check if can download
        if (!$purchase->canDownload()) {
            return response()->json([
                'success' => false,
                'message' => 'لقد تجاوزت الحد الأقصى لعدد التنزيلات',
            ], 403);
        }

        // Generate download link
        $downloadUrl = $purchase->generateDownloadLink();
        
        // Increment download count
        $purchase->incrementDownloads();

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $downloadUrl,
                'expires_in_minutes' => 30,
                'remaining_downloads' => $purchase->max_downloads - $purchase->download_count,
            ],
        ]);
    }

    /**
     * Get purchase details
     * 
     * Get details of a specific book purchase.
     * 
     * @authenticated
     * 
     * @urlParam bookId integer required Book ID. Example: 1
     */
    public function show(Request $request, int $bookId): JsonResponse
    {
        $user = $request->user();
        
        $purchase = BookPurchase::with(['book.author.user', 'book.category'])
            ->where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->where('payment_status', 'completed')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new BookPurchaseResource($purchase),
        ]);
    }

    /**
     * Calculate purchase price based on format
     */
    private function calculatePrice(Book $book, string $format): float
    {
        $price = match($format) {
            'digital' => $book->final_price,
            'physical' => $book->physical_price ?? 0,
            'both' => ($book->final_price + ($book->physical_price ?? 0)) * 0.9, // 10% discount for both
            default => 0,
        };

        return round($price, 2);
    }
}