<?php





namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;

class PayPalService
{
    private function getBaseUrl()
    {
        return config('payment.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function getAccessToken()
    {
        $config = config('payment.paypal');

        $clientId = $config[$config['mode']]['client_id'];
        $secret = $config[$config['mode']]['client_secret'];

        $response = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post($this->getBaseUrl() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials'
            ]);

        return $response['access_token'];
    }

    public function createPayment($amount, $description = '')
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->post($this->getBaseUrl() . '/v2/checkout/orders', [
                    "intent" => "CAPTURE",
                    "purchase_units" => [
                        [
                            "description" => $description,
                            "amount" => [
                                "currency_code" => config('payment.currency'),
                                "value" => number_format($amount, 2, '.', '')
                            ]
                        ]
                    ],
                    "application_context" => [
                        "return_url" => config('payment.paypal.return_url'),
                        "cancel_url" => config('payment.paypal.cancel_url')
                    ]
                ]);

            $data = $response->json();

            return [
                'success' => true,
                // 'order_id' => $data['id'],
                'payment_id' => $data['id'],
                'approval_url' => $this->getApprovalUrl($data),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function executePayment($orderId)
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->post($this->getBaseUrl() . "/v2/checkout/orders/{$orderId}/capture");

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getApprovalUrl($data)
    {
        foreach ($data['links'] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }

    public function calculateFee($amount)
    {
        return round(($amount * 0.029) + 0.30, 2);
    }
}

// namespace App\Services\Payment;

// use PayPal\Rest\ApiContext;
// use PayPal\Auth\OAuthTokenCredential;
// use PayPal\Api\Amount;
// use PayPal\Api\Payer;
// use PayPal\Api\Payment as PayPalPayment;
// use PayPal\Api\PaymentExecution;
// use PayPal\Api\RedirectUrls;
// use PayPal\Api\Transaction as PayPalTransaction;

// class PayPalService
// {
//     private $apiContext;

//     public function __construct()
//     {
//         $this->apiContext = new ApiContext(
//             new OAuthTokenCredential(
//                 config('payment.paypal.sandbox.client_id'),
//                 config('payment.paypal.sandbox.client_secret')
//             )
//         );
//     }

//     public function createPayment($amount, $description = '')
//     {
//         try {
//             $payer = new Payer();
//             $payer->setPaymentMethod('paypal');

//             $amountObj = new Amount();
//             $amountObj->setCurrency('USD')->setTotal($amount);

//             $transaction = new PayPalTransaction();
//             $transaction->setAmount($amountObj)->setDescription($description);

//             $redirectUrls = new RedirectUrls();
//             $redirectUrls->setReturnUrl(config('payment.paypal.return_url'))
//                 ->setCancelUrl(config('payment.paypal.cancel_url'));

//             $payment = new PayPalPayment();
//             $payment->setIntent('sale')
//                 ->setPayer($payer)
//                 ->setRedirectUrls($redirectUrls)
//                 ->setTransactions([$transaction]);

//             $payment->create($this->apiContext);

//             return [
//                 'success' => true,
//                 'payment_id' => $payment->getId(),
//                 'approval_url' => $this->getApprovalUrl($payment),
//             ];
//         } catch (\Exception $e) {
//             return ['success' => false, 'message' => $e->getMessage()];
//         }
//     }

//     public function executePayment($paymentId, $payerId)
//     {
//         try {
//             $payment = PayPalPayment::get($paymentId, $this->apiContext);
//             $execution = new PaymentExecution();
//             $execution->setPayerId($payerId);
//             $result = $payment->execute($execution, $this->apiContext);

//             return [
//                 'success' => true,
//                 'payment' => $result,
//                 'state' => $result->getState(),
//             ];
//         } catch (\Exception $e) {
//             return ['success' => false, 'message' => $e->getMessage()];
//         }
//     }

//     private function getApprovalUrl($payment)
//     {
//         foreach ($payment->getLinks() as $link) {
//             if ($link->getRel() === 'approval_url') {
//                 return $link->getHref();
//             }
//         }
//         return null;
//     }

//     public function calculateFee($amount)
//     {
//         return round(($amount * 0.029) + 0.30, 2);
//     }
// }