<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreatePaymentRequest;
use App\Http\Resources\V1\PaymentResource;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\BookPurchase;
use App\Models\Booking;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PayPalService;
use App\Services\Payment\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Student - Payments
 * 
 * APIs for managing payments
 */
class PaymentController extends Controller
{
    private $paymentService;
    private $paypalService;
    private $stripeService;

    public function __construct(
        PaymentService $paymentService,
        PayPalService $paypalService,
        StripeService $stripeService
    ) {
        $this->paymentService = $paymentService;
        $this->paypalService = $paypalService;
        $this->stripeService = $stripeService;
    }

    /**
     * Get my payments
     * 
     * Get list of all payments for authenticated student.
     * 
     * @authenticated
     * 
     * @queryParam status string Filter by status. Example: completed
     * @queryParam gateway string Filter by gateway. Example: paypal
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->payments()
            ->with(['payable', 'transactions']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by gateway
        if ($request->has('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        $payments = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Create payment for course enrollment
     * 
     * Create payment for a course enrollment.
     * 
     * @authenticated
     * 
     * @urlParam enrollmentId integer required Enrollment ID. Example: 1
     * @bodyParam gateway string Gateway to use (paypal, stripe). Example: paypal
     */
    public function createCoursePayment(CreatePaymentRequest $request, $enrollmentId): JsonResponse
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->with('course')
            ->findOrFail($enrollmentId);

        if ($enrollment->payment_status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'تم الدفع بالفعل لهذه الدورة',
            ], 400);
        }

        $gateway = $request->gateway ?? config('payment.default');
        
        $result = $this->paymentService->createPayment(
            $enrollment,
            $enrollment->price_paid,
            $gateway
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الدفعة بنجاح',
                'data' => [
                    'payment' => new PaymentResource($result['payment']),
                    'approval_url' => $result['approval_url'] ?? null,
                    'client_secret' => $result['client_secret'] ?? null,
                ],
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'فشل إنشاء الدفعة',
        ], 500);
    }

    /**
     * Create payment for book purchase
     * 
     * Create payment for a book purchase.
     * 
     * @authenticated
     * 
     * @urlParam bookId integer required Book ID. Example: 1
     * @bodyParam gateway string Gateway to use. Example: paypal
     */
   public function createBookPayment(CreatePaymentRequest $request, $bookId): JsonResponse
{
    $book = \App\Models\Book::findOrFail($bookId);

    // Determine price based on format or request
    $format = $request->input('format') ?? 'digital'; // or derive from book format

    if ($book->format === 'both') {
        // User must specify which format they want
        $format = $request->input('format') ?? 'digital';
        $price = $format === 'physical' ? $book->physical_price : $book->digital_price;
    } elseif ($book->format === 'pdf') {
        $price = $book->digital_price;
    } else {
        $price = $book->physical_price;
    }

    if (is_null($price) || $price <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'سعر الكتاب غير محدد',
        ], 422);
    }

    // Check if already purchased
    $existingPurchase = BookPurchase::where('user_id', $request->user()->id)
        ->where('book_id', $bookId)
        ->where('payment_status', 'completed')
        ->first();

    if ($existingPurchase) {
        return response()->json([
            'success' => false,
            'message' => 'لقد قمت بشراء هذا الكتاب بالفعل',
        ], 400);
    }

   $gateway = $request->gateway ?? config('payment.default');

$purchase = BookPurchase::create([
    'user_id'        => $request->user()->id,
    'book_id'        => $bookId,
    'price_paid'     => $price,
    'format'         => $format,
    'payment_method' => $gateway,   // ← added
    'payment_status' => 'pending',
]);
    $result  = $this->paymentService->createPayment($purchase, $price, $gateway);


        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الدفعة بنجاح',
                'data' => [
                    'payment' => new PaymentResource($result['payment']),
                    'approval_url' => $result['approval_url'] ?? null,
                    'client_secret' => $result['client_secret'] ?? null,
                ],
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'فشل إنشاء الدفعة',
        ], 500);
    }

    /**
     * Create payment for booking
     * 
     * Create payment for an instructor booking.
     * 
     * @authenticated
     * 
     * @urlParam bookingId integer required Booking ID. Example: 1
     * @bodyParam gateway string Gateway to use. Example: paypal
     */
    public function createBookingPayment(CreatePaymentRequest $request, $bookingId): JsonResponse
    {
        $booking = Booking::where('user_id', $request->user()->id)
            ->with('instructor')
            ->findOrFail($bookingId);

        if ($booking->payment_status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'تم الدفع بالفعل لهذا الحجز',
            ], 400);
        }

        $gateway = $request->gateway ?? config('payment.default');
        
        $result = $this->paymentService->createPayment(
            $booking,
            $booking->price,
            $gateway
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الدفعة بنجاح',
                'data' => [
                    'payment' => new PaymentResource($result['payment']),
                    'approval_url' => $result['approval_url'] ?? null,
                    'client_secret' => $result['client_secret'] ?? null,
                ],
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'فشل إنشاء الدفعة',
        ], 500);
    }

    /**
     * Execute PayPal payment
     * 
     * Execute PayPal payment after user approval.
     * 
     * @authenticated
     * 
     * @bodyParam paymentId string required PayPal payment ID. Example: PAYID-M123456
     * @bodyParam PayerID string required PayPal payer ID. Example: ABC123
     */
    public function executePayPalPayment(Request $request): JsonResponse
    {
        $request->validate([
            'paymentId' => 'required|string',
            'PayerID' => 'required|string',
        ]);

        $payment = Payment::where('gateway_payment_id', $request->paymentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'تم تنفيذ هذه الدفعة بالفعل',
            ], 400);
        }

        $result = $this->paypalService->executePayment($request->paymentId, $request->PayerID);

        if ($result['success']) {
            $payment->markAsPaid($request->paymentId);

            return response()->json([
                'success' => true,
                'message' => 'تم الدفع بنجاح',
                'data' => new PaymentResource($payment->fresh()->load('payable')),
            ]);
        }

        $payment->update(['status' => 'failed']);

        return response()->json([
            'success' => false,
            'message' => 'فشل تنفيذ الدفع',
        ], 400);
    }

    /**
     * Verify Stripe payment
     * 
     * Verify Stripe payment intent status.
     * 
     * @authenticated
     * 
     * @bodyParam payment_intent_id string required Stripe payment intent ID. Example: pi_123
     */
    public function verifyStripePayment(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $payment = Payment::where('gateway_payment_id', $request->payment_intent_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $result = $this->stripeService->getPaymentIntent($request->payment_intent_id);

        if ($result['success'] && $result['status'] === 'succeeded') {
            $payment->markAsPaid($request->payment_intent_id);

            return response()->json([
                'success' => true,
                'message' => 'تم التحقق من الدفع بنجاح',
                'data' => new PaymentResource($payment->fresh()->load('payable')),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'لم يتم إتمام الدفع بعد',
            'status' => $result['status'] ?? 'unknown',
        ], 400);
    }

    /**
     * Get payment details
     * 
     * Get details of a specific payment.
     * 
     * @authenticated
     * 
     * @urlParam id integer required Payment ID. Example: 1
     */
    public function show(Request $request, $id): JsonResponse
    {
        $payment = Payment::where('user_id', $request->user()->id)
            ->with(['payable', 'transactions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Request refund
     * 
     * Request a refund for a completed payment.
     * 
     * @authenticated
     * 
     * @urlParam id integer required Payment ID. Example: 1
     * @bodyParam reason text Refund reason. Example: لم أتمكن من حضور الدورة
     */
    public function requestRefund(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'يرجى تحديد سبب الاسترداد',
            'reason.max' => 'السبب طويل جداً',
        ]);

        $payment = Payment::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (!$payment->is_refundable) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن استرداد هذه الدفعة',
            ], 400);
        }

        // Create refund request (would be processed by admin)
        $payment->update([
            'metadata' => array_merge($payment->metadata ?? [], [
                'refund_requested' => true,
                'refund_request_reason' => $request->reason,
                'refund_requested_at' => now()->toISOString(),
            ]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الاسترداد بنجاح. سيتم مراجعته قريباً.',
        ]);
    }



    /**
 * Create payment for workshop registration
 * * @authenticated
 * @urlParam registrationId integer required Registration ID. Example: 8
 * @bodyParam gateway string Gateway to use (paypal, stripe). Example: paypal
 */
public function createWorkshopPayment(CreatePaymentRequest $request, $registrationId): JsonResponse
{
    // التأكد أن التسجيل يخص الطالب الحالي ومع تحميل بيانات الورشة
    $registration = \App\Models\WorkshopRegistration::where('user_id', $request->user()->id)
        ->with('workshop')
        ->findOrFail($registrationId);

    // التحقق مما إذا كان الدفع قد تم مسبقاً
    if ($registration->payment_status === 'completed' || $registration->status === 'confirmed') {
        return response()->json([
            'success' => false,
            'message' => 'تم الدفع بالفعل لهذا التسجيل أو أن الحجز مؤكد',
        ], 400);
    }

    $gateway = $request->gateway ?? config('payment.default');
    
    // استدعاء خدمة الدفع الموحدة
    // نمرر الـ registration كـ Payable والـ price_paid كقيمة
    $result = $this->paymentService->createPayment(
        $registration,
        $registration->price_paid,
        $gateway
    );

    if ($result['success']) {
        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الدفعة بنجاح',
            'data' => [
                'payment' => new PaymentResource($result['payment']),
                'approval_url' => $result['approval_url'] ?? null,
                'client_secret' => $result['client_secret'] ?? null,
            ],
        ], 201);
    }

    return response()->json([
        'success' => false,
        'message' => $result['message'] ?? 'فشل إنشاء الدفعة',
    ], 500);
}
}
