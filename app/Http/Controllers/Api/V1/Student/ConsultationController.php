<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ConsultationResource;
use App\Http\Resources\ConsultationCollectionResource;
use App\Models\Consultation;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    /**
     * Get all student consultations
     * GET /api/v1/student/consultations
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
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

            $query = Consultation::where('student_id', $user->id);

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by priority
            if ($request->has('priority') && $request->priority) {
                $query->where('priority', $request->priority);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $consultations = $query
                ->with(['instructor', 'course'])
                ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'data' => ConsultationResource::collection($consultations->items()),
                'pagination' => [
                    'total' => $consultations->total(),
                    'per_page' => $consultations->perpage(),
                    'current_page' => $consultations->currentpage(),
                    'last_page' => $consultations->lastpage(),
                    'from' => $consultations->firstItem(),
                    'to' => $consultations->lastItem(),
                ],
                'summary' => [
                    'total_consultations' => $user->consultations()->count(),
                    'pending_consultations' => $user->consultations()->where('status', 'pending')->count(),
                    'answered_consultations' => $user->consultations()->where('status', 'answered')->count(),
                    'closed_consultations' => $user->consultations()->where('status', 'closed')->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving consultations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new consultation
     * POST /api/v1/student/consultations
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
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
                'title' => 'required|string|max:255',
                'question' => 'required|string|min:10|max:5000',
                'course_id' => 'nullable|exists:courses,id',
                'priority' => 'sometimes|in:low,medium,high',
            ]);

            $consultation = Consultation::create([
                'student_id' => $user->id,
                'title' => $validated['title'],
                'question' => $validated['question'],
                'course_id' => $validated['course_id'] ?? null,
                'priority' => $validated['priority'] ?? 'medium',
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Consultation created successfully',
                'data' => new ConsultationResource(
                    $consultation->load(['instructor', 'course'])
                ),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating consultation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single consultation
     * GET /api/v1/student/consultations/{id}
     * 
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
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

            $consultation = Consultation::where('student_id', $user->id)
                ->with(['instructor', 'course'])
                ->find($id);

            if (!$consultation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consultation not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new ConsultationResource($consultation),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving consultation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update consultation
     * PUT /api/v1/student/consultations/{id}
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
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

            $consultation = Consultation::where('student_id', $user->id)->find($id);

            if (!$consultation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consultation not found',
                ], 404);
            }

            // Only allow updating pending consultations
            if ($consultation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update answered or closed consultations',
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'question' => 'sometimes|string|min:10|max:5000',
                'priority' => 'sometimes|in:low,medium,high',
            ]);

            $consultation->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Consultation updated successfully',
                'data' => new ConsultationResource(
                    $consultation->load(['instructor', 'course'])
                ),
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
                'message' => 'Error updating consultation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete consultation
     * DELETE /api/v1/student/consultations/{id}
     * 
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
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

            $consultation = Consultation::where('student_id', $user->id)->find($id);

            if (!$consultation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consultation not found',
                ], 404);
            }

            // Only allow deleting pending consultations
            if ($consultation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete answered or closed consultations',
                ], 403);
            }

            $consultation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Consultation deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting consultation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}