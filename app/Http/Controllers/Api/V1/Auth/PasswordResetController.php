<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Email\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function __construct(
        private EmailVerificationService $emailService
    ) {}

    /**
     * Send password reset code
     */
    public function forgotPassword(Request $request): JsonResponse
    {
     
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يجب إدخال بريد إلكتروني صحيح',
            'email.exists' => 'البريد الإلكتروني غير مسجل',
        ]);

        $user = User::where('email', $request->email)->first();
        
        // Generate 6-digit code
        $code = random_int(100000, 999999);
        
        // Store in password_resets table
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        // Send email
        $this->emailService->sendPasswordResetCode($user, $code);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز إعادة تعيين كلمة المرور إلى بريدك الإلكتروني',
        ]);
    }

    /**
     * Verify reset code
     */
    public function verifyResetCode(Request $request): JsonResponse
    {
        
        // dd($request->headers->all(), $request->all());
        
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|numeric|digits:6',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'code.required' => 'رمز التحقق مطلوب',
            'code.digits' => 'رمز التحقق يجب أن يكون 6 أرقام',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'رمز إعادة التعيين غير صحيح',
            ], 400);
        }

        if (!Hash::check($request->code, $reset->token)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز إعادة التعيين غير صحيح',
            ], 400);
        }

        // Check if code is expired (15 minutes)
        if (now()->diffInMinutes($reset->created_at) > 15) {
            return response()->json([
                'success' => false,
                'message' => 'رمز إعادة التعيين منتهي الصلاحية',
            ], 400);
        }

        // Generate temporary token
        $tempToken = Str::random(60);
        
        DB::table('password_resets')
            ->where('email', $request->email)
            ->update(['token' => Hash::make($tempToken)]);

        return response()->json([
            'success' => true,
            'message' => 'رمز التحقق صحيح',
            'data' => [
                'temp_token' => $tempToken,
            ],
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'temp_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'temp_token.required' => 'رمز التحقق مطلوب',
            'password.required' => 'كلمة المرور الجديدة مطلوبة',
            'password.min' => 'يجب أن تكون كلمة المرور 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset || !Hash::check($request->temp_token, $reset->token)) {
            return response()->json([
                'success' => false,
                'message' => 'رابط إعادة التعيين غير صحيح',
            ], 400);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete reset record
        DB::table('password_resets')->where('email', $request->email)->delete();

        // Revoke all user tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
        ]);
    }
}