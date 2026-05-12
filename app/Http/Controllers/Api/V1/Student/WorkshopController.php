<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkshopRegistrationRequest;
use App\Http\Resources\V1\WorkshopRegistrationResource;
use App\Http\Resources\V1\WorkshopResource;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Student - Workshops
 * 
 * APIs for student workshop registration and management
 */
class WorkshopController extends Controller
{
    /**
     * Get my registrations
     * 
     * Get list of all workshop registrations for authenticated student.
     * 
     * @authenticated
     * 
     * @queryParam status string Filter by status. Example: confirmed
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->workshopRegistrations()
            ->with('workshop.instructor.user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $registrations = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => WorkshopRegistrationResource::collection($registrations),
            'meta' => [
                'current_page' => $registrations->currentPage(),
                'last_page' => $registrations->lastPage(),
                'total' => $registrations->total(),
            ],
        ]);
    }

    /**
     * Register for workshop
     * 
     * Register the authenticated student in a workshop.
     * 
     * @authenticated
     * 
     * @urlParam workshopId integer required Workshop ID. Example: 1
     */
    public function register(WorkshopRegistrationRequest $request, int $workshopId): JsonResponse
    {
        $user = $request->user();
        $workshop = Workshop::findOrFail($workshopId);

        // Check if can register
        if (!$workshop->canRegister) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن التسجيل في هذه الورشة حالياً',
            ], 400);
        }

        // Check if already registered
        if ($workshop->isRegisteredBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'أنت مسجل بالفعل في هذه الورشة',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $paymentStatus = $workshop->price == 0 ? 'completed' : 'pending';

            $registration = WorkshopRegistration::create([
                'user_id' => $user->id,
                'workshop_id' => $workshop->id,
                'price_paid' => $workshop->price,
                'payment_status' => $paymentStatus,
                'status' => $paymentStatus === 'completed' ? 'confirmed' : 'pending',
            ]);

            // if ($paymentStatus === 'completed') {
            //     $workshop->incrementRegistrations();
            // }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $paymentStatus === 'completed' 
                    ? 'تم التسجيل في الورشة بنجاح' 
                    : 'يرجى إتمام عملية الدفع',
                'data' => new WorkshopRegistrationResource($registration->load('workshop')),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
      
            'message' => $e->getMessage(), 
            'file' => $e->getFile(),
            'line' => $e->getLine(),
                'success' => false,
                // 'message' => 'حدث خطأ أثناء التسجيل',
            ], 500);
        }
    }

    /**
     * Get registration details
     * 
     * Get details of a specific registration.
     * 
     * @authenticated
     * 
     * @urlParam id integer required Registration ID. Example: 1
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $registration = $request->user()
            ->workshopRegistrations()
            ->with('workshop.instructor.user')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new WorkshopRegistrationResource($registration),
        ]);
    }

    /**
     * Cancel registration
     * 
     * Cancel a workshop registration.
     * 
     * @authenticated
     * 
     * @urlParam id integer required Registration ID. Example: 1
     * @bodyParam reason text Cancellation reason. Example: تغيير في الظروف
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'sometimes|string|max:500',
        ]);

        $registration = $request->user()
            ->workshopRegistrations()
            ->findOrFail($id);

        if (!$registration->can_be_cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا التسجيل',
            ], 400);
        }

        $registration->cancel($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء التسجيل بنجاح',
        ]);
    }

    /**
     * Download certificate
     * 
     * Download workshop certificate (if issued).
     * 
     * @authenticated
     * 
     * @urlParam id integer required Registration ID. Example: 1
     */
   


    public function downloadCertificate(Request $request, $identifier): JsonResponse
{
    // البحث عن التسجيل باستخدام رقم الشهادة أو الـ ID لضمان مرونة الكود
    $registration = $request->user()
        ->workshopRegistrations()
        ->where(function($query) use ($identifier) {
            $query->where('certificate_number', $identifier)
                  ->orWhere('id', $identifier);
        })
        ->firstOrFail();

    // التحقق من أن الشهادة قد صدرت بالفعل
    if (!$registration->certificate_issued) {
        return response()->json([
            'success' => false,
            'message' => 'الشهادة غير متوفرة بعد أو لم يتم إتمام الدفع',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'certificate_number' => $registration->certificate_number,
            'download_url' => route('certificates.download', $registration->certificate_number),
            'issued_at' => $registration->certificate_issued_at ? $registration->certificate_issued_at->toISOString() : null,
        ],
    ]);
    //you need to create a route for 'certificates.download' that points to a method responsible for generating and returning the actual certificate file (PDF, image, etc.) based on the certificate number.
}

    public function upcoming(Request $request): JsonResponse
{
    $workshops = Workshop::upcoming()
        ->published()
        ->limit(5)
        ->get();

    return response()->json([
        'success' => true,
        'data' => WorkshopResource::collection($workshops),
    ]);
}


/**
 * عرض الورش التي حضرها الطالب أو انتهت
 */
public function completed(Request $request)
{
    $user = $request->user();

    $registrations = $user->workshopRegistrations() // علاقة الطالب بالتسجيلات
        ->whereIn('status', ['attended', 'confirmed']) // ورش مؤكدة أو تم حضورها
        ->whereHas('workshop', function ($query) {
            $query->where('end_date', '<', now()) // انتهت قبل الآن
                  ->orWhere('status', 'completed'); // أو حالتها مكتملة
        })
        ->with('workshop')
        ->latest()
        ->paginate(10);

    return response()->json([
        'success' => true,
        'data' => WorkshopRegistrationResource::collection($registrations),
        'meta' => [
            'total' => $registrations->total(),
        ]
    ]);
}
}