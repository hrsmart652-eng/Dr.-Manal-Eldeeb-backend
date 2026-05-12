<?php

namespace App\Services\Payment;

use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('payment.stripe.secret'));
    }

    public function createPaymentIntent($amount, $metadata = [])
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'metadata' => $metadata,
            ]);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function confirmPaymentIntent($paymentIntentId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            return [
                'success' => true,
                'status' => $paymentIntent->status,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function calculateFee($amount)
    {
        return round(($amount * 0.029) + 0.30, 2);
    }
}