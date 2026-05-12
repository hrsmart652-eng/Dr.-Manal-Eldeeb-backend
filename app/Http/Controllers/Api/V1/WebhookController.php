<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\StripeService;
use App\Services\Payment\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @group Webhooks
 * 
 * Payment gateway webhook handlers
 */
class WebhookController extends Controller
{
    private $stripeService;
    private $paypalService;

    public function __construct(StripeService $stripeService, PayPalService $paypalService)
    {
        $this->stripeService = $stripeService;
        $this->paypalService = $paypalService;
    }

    /**
     * Handle Stripe webhook
     * 
     * Handle incoming webhooks from Stripe.
     * 
     * @response 200 {
     *   "success": true
     * }
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Verify webhook signature
        $result = $this->stripeService->verifyWebhook($payload, $signature);

        if (!$result['success']) {
            Log::error('Stripe webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $result['event'];

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        try {
            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event type: ' . $event->type);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'error' => $e->getMessage(),
                'event_type' => $event->type,
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle PayPal webhook
     * 
     * Handle incoming webhooks from PayPal.
     * 
     * @response 200 {
     *   "success": true
     * }
     */
    public function handlePayPalWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        Log::info('PayPal webhook received', [
            'event_type' => $payload['event_type'] ?? 'unknown',
        ]);

        try {
            // Verify webhook (simplified - implement full verification in production)
            $verified = $this->paypalService->verifyWebhook($headers, $payload);

            if (!$verified) {
                Log::error('PayPal webhook verification failed');
                return response()->json(['error' => 'Verification failed'], 400);
            }

            $eventType = $payload['event_type'] ?? null;

            // Handle different event types
            switch ($eventType) {
                case 'PAYMENT.SALE.COMPLETED':
                    $this->handlePayPalSaleCompleted($payload['resource']);
                    break;

                case 'PAYMENT.SALE.REFUNDED':
                    $this->handlePayPalSaleRefunded($payload['resource']);
                    break;

                default:
                    Log::info('Unhandled PayPal webhook event type: ' . $eventType);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error processing PayPal webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle successful payment intent
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        $payment = Payment::where('gateway_payment_id', $paymentIntent->id)->first();

        if (!$payment) {
            Log::warning('Payment not found for payment_intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        if ($payment->status === 'completed') {
            Log::info('Payment already marked as completed', [
                'payment_id' => $payment->id,
            ]);
            return;
        }

        // Get card details if available
        if (isset($paymentIntent->charges->data[0]->payment_method_details->card)) {
            $card = $paymentIntent->charges->data[0]->payment_method_details->card;
            $payment->card_last4 = $card->last4 ?? null;
            $payment->card_brand = $card->brand ?? null;
            $payment->save();
        }

        $payment->markAsPaid($paymentIntent->id);

        Log::info('Payment marked as paid from Stripe webhook', [
            'payment_id' => $payment->id,
            'payment_number' => $payment->payment_number,
        ]);
    }

    /**
     * Handle failed payment intent
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        $payment = Payment::where('gateway_payment_id', $paymentIntent->id)->first();

        if (!$payment) {
            return;
        }

        $failureMessage = $paymentIntent->last_payment_error->message ?? 'Unknown error';

        $payment->update([
            'status' => 'failed',
            'metadata' => array_merge($payment->metadata ?? [], [
                'failure_reason' => $failureMessage,
                'failed_at' => now()->toISOString(),
            ]),
        ]);

        Log::info('Payment marked as failed from Stripe webhook', [
            'payment_id' => $payment->id,
            'reason' => $failureMessage,
        ]);
    }

    /**
     * Handle refunded charge
     */
    private function handleChargeRefunded($charge)
    {
        // Find payment by charge ID or payment intent ID
        $payment = Payment::where('gateway_payment_id', $charge->payment_intent)
            ->orWhere('gateway_payment_id', $charge->id)
            ->first();

        if (!$payment) {
            return;
        }

        $refundAmount = $charge->amount_refunded / 100; // Convert from cents

        $payment->update([
            'status' => 'refunded',
            'refund_amount' => $refundAmount,
            'refunded_at' => now(),
        ]);

        Log::info('Payment marked as refunded from Stripe webhook', [
            'payment_id' => $payment->id,
            'refund_amount' => $refundAmount,
        ]);
    }

    /**
     * Handle PayPal sale completed
     */
    private function handlePayPalSaleCompleted($resource)
    {
        // PayPal payment ID is in the parent_payment field
        $paymentId = $resource['parent_payment'] ?? null;

        if (!$paymentId) {
            return;
        }

        $payment = Payment::where('gateway_payment_id', $paymentId)->first();

        if (!$payment) {
            return;
        }

        $payment->markAsPaid($paymentId);

        Log::info('Payment marked as paid from PayPal webhook', [
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Handle PayPal sale refunded
     */
    private function handlePayPalSaleRefunded($resource)
    {
        $paymentId = $resource['parent_payment'] ?? null;

        if (!$paymentId) {
            return;
        }

        $payment = Payment::where('gateway_payment_id', $paymentId)->first();

        if (!$payment) {
            return;
        }

        $refundAmount = $resource['amount']['total'] ?? 0;

        $payment->update([
            'status' => 'refunded',
            'refund_amount' => $refundAmount,
            'refunded_at' => now(),
        ]);

        Log::info('Payment marked as refunded from PayPal webhook', [
            'payment_id' => $payment->id,
        ]);
    }
}