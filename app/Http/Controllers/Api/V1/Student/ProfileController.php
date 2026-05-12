<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\StudentProfileResource;
// use App\Http\Resources\V1\EnrollmentResource;
// use App\Http\Resources\V1\ConsultationResource;
// use App\Models\User;
// use App\Models\Consultation;
// use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get authenticated student profile
     * GET /api/v1/student/profile
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            if (!$user->isStudent()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is restricted to students only',
                ], 403);
            }

            // Load relationships
            $user->load([
                'enrolledCourses',
                'enrolledCourses.instructor',
                'enrolledCourses.books',
                'consultations',
                'consultations.instructor',
                'consultations.course'
            ]);

            return response()->json([
                'success' => true,
                'data' => new StudentProfileResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update student profile
     * PUT /api/v1/student/profile
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            if (!$user->isStudent()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is restricted to students only',
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
                'bio' => 'sometimes|nullable|string|max:1000',
                'gender' => 'sometimes|nullable|in:male,female',
                'birth_date' => 'sometimes|nullable|date|before:today',
                'city' => 'sometimes|nullable|string|max:100',
                'country' => 'sometimes|nullable|string|max:100',
            ]);

            $user->update($validated);

            // Reload relationships
            $user->load([
                'enrolledCourses',
                'enrolledCourses.instructor',
                'enrolledCourses.books',
                'consultations',
                'consultations.instructor',
                'consultations.course'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => new StudentProfileResource($user),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload student avatar
     * POST /api/v1/student/profile/avatar
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            if ($user->type !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is restricted to students only',
                ], 403);
            }

            $validated = $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars/students', 'public');
            $user->update(['avatar' => $avatarPath]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatar' => asset('storage/' . $user->avatar),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete student avatar
     * DELETE /api/v1/student/profile/avatar
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAvatar()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            if ($user->type !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is restricted to students only',
                ], 403);
            }

            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->update(['avatar' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}