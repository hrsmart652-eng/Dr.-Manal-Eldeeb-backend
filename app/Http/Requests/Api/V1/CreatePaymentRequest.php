<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'gateway' => 'sometimes|in:paypal,stripe',
             'format'  => 'nullable|string|in:digital,physical',
            'payment_method' => 'sometimes|in:paypal,stripe,credit_card',
            'return_url' => 'sometimes|url',
            'cancel_url' => 'sometimes|url',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'gateway.in' => 'بوابة الدفع يجب أن تكون PayPal أو Stripe',
            'payment_method.in' => 'طريقة الدفع غير صالحة',
            'return_url.url' => 'رابط العودة يجب أن يكون صحيحاً',
            'cancel_url.url' => 'رابط الإلغاء يجب أن يكون صحيحاً',
        ];
    }
}