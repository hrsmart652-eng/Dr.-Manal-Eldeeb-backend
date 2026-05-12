<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkshopRegistrationRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرحاً له بإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        // تأكدي أن القيمة true ليسمح Laravel بتنفيذ الـ Validation
        return true; 
    }

    /**
     * شروط التحقق التي تنطبق على الطلب.
     */
    public function rules(): array
    {
        return [
            // التحقق من طريقة الدفع (مثلاً: stripe, paypal, wallet)
            'payment_method' => 'required|string|in:stripe,paypal,wallet,bank_transfer',
            
            // إذا كان هناك كود خصم اختياري
            'promo_code' => 'nullable|string|max:20',
            
            // التحقق من وجود الورشة وأنها ليست ممتلئة أو منتهية (يمكنكِ إضافتها هنا أو في الـ Controller)
            // ملاحظة: الـ workshop_id غالباً بيجي من الـ URL وليس من الـ Body
        ];
    }

    /**
     * تخصيص رسائل الخطأ بالعربية.
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'يجب اختيار طريقة الدفع.',
            'payment_method.in' => 'طريقة الدفع المختارة غير مدعومة.',
            'promo_code.max' => 'كود الخصم يجب ألا يتجاوز 20 حرفاً.',
        ];
    }
}