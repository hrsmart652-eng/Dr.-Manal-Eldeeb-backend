<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
// use App\Mail\ContactFormReceived;

/**
 * @group Contact
 * 
 * Contact form submission
 */
class ContactController extends Controller
{
    /**
     * Submit contact form
     * 
     * Submit a contact/support message.
     * 
     * @bodyParam name string required Full name. Example: أحمد محمد
     * @bodyParam email string required Email address. Example: ahmad@example.com
     * @bodyParam phone string Phone number. Example: +201234567890
     * @bodyParam subject string required Message subject. Example: استفسار عن الدورات
     * @bodyParam message text required Message content. Example: أريد معرفة المزيد عن دورات القيادة
     * @bodyParam type string Message type. Example: general
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.",
     *   "data": {
     *     "id": 1,
     *     "reference_number": "CM-001"
     *   }
     * }
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:2000',
            'type' => 'nullable|in:general,support,suggestion,complaint,other',
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'subject.required' => 'الموضوع مطلوب',
            'message.required' => 'الرسالة مطلوبة',
            'message.min' => 'يجب أن تكون الرسالة 10 أحرف على الأقل',
            'message.max' => 'يجب ألا تتجاوز الرسالة 2000 حرف',
        ]);

        // Create contact message
        $contactMessage = ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            // 'type' => $validated['type'] ?? 'general',
            'status' => 'new',
            // 'ip_address' => $request->ip(),
            // 'user_agent' => $request->userAgent(),
        ]);

        // Send notification email to admin (optional)
        try {
            // Mail::to(config('mail.admin_email'))->send(new ContactFormReceived($contactMessage));
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send contact form email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.',
            'data' => [
                'id' => $contactMessage->id,
                'reference_number' => 'CM-' . str_pad($contactMessage->id, 6, '0', STR_PAD_LEFT),
            ],
        ], 201);
    }

    /**
     * Get contact info
     * 
     * Get academy contact information.
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "email": "info@leadersacademy.com",
     *     "phone": "+966 12 345 6789",
     *     "whatsapp": "+966 50 123 4567",
     *     "address": "الرياض، المملكة العربية السعودية",
     *     "working_hours": "الأحد - الخميس: 9 صباحاً - 5 مساءً",
     *     "social_media": {
     *       "facebook": "...",
     *       "twitter": "...",
     *       "instagram": "...",
     *       "linkedin": "..."
     *     }
     *   }
     * }
     */
    public function info(): JsonResponse
    {
        $contactInfo = [
            'email' => 'info@leadersacademy.com',
            'phone' => '+966 12 345 6789',
            'whatsapp' => '+966 50 123 4567',
            'address' => 'الرياض، المملكة العربية السعودية',
            'working_hours' => 'الأحد - الخميس: 9 صباحاً - 5 مساءً',
            'social_media' => [
                'facebook' => 'https://facebook.com/leadersacademy',
                'twitter' => 'https://twitter.com/leadersacademy',
                'instagram' => 'https://instagram.com/leadersacademy',
                'linkedin' => 'https://linkedin.com/company/leadersacademy',
                'youtube' => 'https://youtube.com/@leadersacademy',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $contactInfo,
        ]);
    }
}