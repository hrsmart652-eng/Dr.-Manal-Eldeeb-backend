<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\Email\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private EmailVerificationService $emailService
    ) {}

    /**
     * Register new user
     * 
     * @bodyParam name string required User's full name. Example: أحمد محمد
     * @bodyParam email string required Email address. Example: ahmad@example.com
     * @bodyParam phone string Phone number. Example: +201234567890
     * @bodyParam password string required Password (min 8 chars). Example: password123
     * @bodyParam password_confirmation string required Password confirmation. Example: password123
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Generate 6-digit verification code
        $verificationCode = random_int(100000, 999999);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'type' => 'student',
            'verification_code' => $verificationCode,
            'code_expires_at' => now()->addMinutes(15),
        ]);

        // Send verification email
        $this->emailService->sendVerificationCode($user, $verificationCode);

        // Generate Passport token with scope
        $tokenResult = $user->createToken('auth_token', ['student']);
        $token = $tokenResult->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'تم التسجيل بنجاح. يرجى التحقق من بريدك الإلكتروني.',
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => now()->addDays(15)->timestamp,
            ],
        ], 201);
    }

    /**
     * User login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
            ], 401);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'الحساب غير نشط. يرجى التواصل مع الدعم الفني.',
            ], 403);
        }

        // Determine scope based on user type
        $scope = [$user->type];
        $tokenResult = $user->createToken('auth_token', $scope);
        $token = $tokenResult->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => new UserResource($user->load('instructor')),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => now()->addDays(15)->timestamp,
            ],
        ]);
    }

    /**
     * Verify email with code
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|numeric|digits:6',
        ], [
            'code.required' => 'رمز التحقق مطلوب',
            'code.numeric' => 'رمز التحقق يجب أن يكون أرقام فقط',
            'code.digits' => 'رمز التحقق يجب أن يكون 6 أرقام',
        ]);

        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'البريد الإلكتروني موثق بالفعل',
            ], 400);
        }

        if ($user->verification_code != $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح',
            ], 400);
        }

        if (now()->greaterThan($user->code_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق منتهي الصلاحية',
            ], 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'verification_code' => null,
            'code_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق من البريد الإلكتروني بنجاح',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Resend verification code
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'البريد الإلكتروني موثق بالفعل',
            ], 400);
        }

        $verificationCode = random_int(100000, 999999);
        
        $user->update([
            'verification_code' => $verificationCode,
            'code_expires_at' => now()->addMinutes(15),
        ]);

        $this->emailService->sendVerificationCode($user, $verificationCode);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق مرة أخرى',
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    /**
     * Get current user
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()->load('instructor')),
        ]);
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'bio' => ['sometimes', 'string'],
            'birth_date' => ['sometimes', 'date'],
            'gender' => ['sometimes', 'in:male,female'],
            'country' => ['sometimes', 'string', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old avatar
        if ($user->avatar) {
            \Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الصورة الشخصية بنجاح',
            'data' => [
                'avatar' => asset('storage/' . $path),
            ],
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة',
            'password.required' => 'كلمة المرور الجديدة مطلوبة',
            'password.min' => 'يجب أن تكون كلمة المرور الجديدة 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
        return response()->json([
    'message' => 'Password updated successfully'
], 200);

        // Revoke all tokens except current
        $user->tokens()->where('id', '!=', $user->token()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }
}