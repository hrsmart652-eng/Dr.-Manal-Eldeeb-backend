<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private $paypalService;
    private $stripeService;

    public function __construct(PayPalService $paypal, StripeService $stripe)
    {
        $this->paypalService = $paypal;
        $this->stripeService = $stripe;
    }

    /**
     * Create payment for any payable entity
     */
    public function createPayment($payable, $amount, $gateway = null)
    {
        $gateway = $gateway ?? config('payment.default');

        try {
            DB::beginTransaction();

            // Calculate fee
            $fee = $gateway === 'paypal' 
                ? $this->paypalService->calculateFee($amount)
                : $this->stripeService->calculateFee($amount);

            // Create payment record
            $payment = Payment::create([
                'user_id' => auth()->id(),
                'payable_type' => get_class($payable),
                'payable_id' => $payable->id,
                'amount' => $amount,
                'currency' => config('payment.currency', 'USD'),
                'fee' => $fee,
                'payment_method' => $gateway,
                'gateway' => $gateway,
                'status' => 'pending',
                'ip_address' => request()->ip(),
            ]);

            // Create payment with gateway
            if ($gateway === 'paypal') {
                $result = $this->createPayPalPayment($payment, $payable);
            } else {
                $result = $this->createStripePayment($payment, $payable);
            }

            if (!$result['success']) {
                DB::rollBack();
                return $result;
            }

            DB::commit();

            return [
                'success' => true,
                'payment' => $payment->fresh(),
                'approval_url' => $result['approval_url'] ?? null,
                'client_secret' => $result['client_secret'] ?? null,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'payable_type' => get_class($payable),
                'payable_id' => $payable->id,
            ]);

            return [
                'success' => false,
                'message' => 'فشل إنشاء الدفعة',
            ];
        }
    }

    /**
     * Create PayPal payment
     */
    private function createPayPalPayment($payment, $payable)
    {
        $description = $this->getPaymentDescription($payable);

        $result = $this->paypalService->createPayment(
            $payment->amount,
            $description
        );

        if ($result['success']) {
            $payment->update([
                // 'gateway_payment_id' => $result['payment_id'],
                'gateway_payment_id' => $result['payment_id'],
            ]);

            return [
                'success' => true,
                'approval_url' => $result['approval_url'],
            ];
        }

        return $result;
    }

    /**
     * Create Stripe payment
     */
    private function createStripePayment($payment, $payable)
    {
        $metadata = [
            'payment_id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'payable_type' => class_basename($payment->payable_type),
            'payable_id' => $payment->payable_id,
        ];

        $result = $this->stripeService->createPaymentIntent(
            $payment->amount,
            $metadata
        );

        if ($result['success']) {
            $payment->update([
                'gateway_payment_id' => $result['payment_intent_id'],
            ]);

            return [
                'success' => true,
                'client_secret' => $result['client_secret'],
            ];
        }

        return $result;
    }

    /**
     * Get payment description based on payable type
     */
    private function getPaymentDescription($payable)
    {
        $type = class_basename(get_class($payable));

        if ($type === 'Enrollment' && isset($payable->course)) {
            return 'Payment for course: ' . $payable->course->title_en;
        }

        if ($type === 'BookPurchase' && isset($payable->book)) {
            return 'Payment for book: ' . $payable->book->title_en;
        }

        if ($type === 'Booking' && isset($payable->instructor)) {
            return 'Payment for consultation with: ' . $payable->instructor->user->name;
        }

        return 'Payment for ' . $type;
    }

    /**
     * Process refund
     */
    public function processRefund(Payment $payment, $amount = null, $reason = null)
    {
        if (!$payment->is_refundable) {
            return [
                'success' => false,
                'message' => 'لا يمكن استرداد هذه الدفعة',
            ];
        }

        try {
            DB::beginTransaction();

            $refundAmount = $amount ?? $payment->amount;

            // Process refund with gateway
            if ($payment->gateway === 'paypal') {
                $result = $this->paypalService->refundPayment(
                    $payment->gateway_payment_id,
                    $refundAmount
                );
            } else {
                $result = $this->stripeService->createRefund(
                    $payment->gateway_payment_id,
                    $refundAmount,
                    $reason
                );
            }

            if (!$result['success']) {
                DB::rollBack();
                return $result;
            }

            // Update payment record
            $payment->update([
                'status' => 'refunded',
                'refund_amount' => $refundAmount,
                'refund_reason' => $reason,
                'refunded_at' => now(),
            ]);

            // Create refund transaction
            Transaction::create([
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'type' => 'refund',
                'amount' => -$refundAmount,
                'currency' => $payment->currency,
                'status' => 'completed',
                'description' => 'Refund: ' . ($reason ?? 'No reason provided'),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم استرداد المبلغ بنجاح',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Refund processing failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);

            return [
                'success' => false,
                'message' => 'فشل معالجة الاسترداد',
            ];
        }
    }
}