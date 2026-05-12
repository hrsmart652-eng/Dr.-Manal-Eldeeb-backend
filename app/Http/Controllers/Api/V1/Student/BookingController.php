<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use App\Models\Booking;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @group Student - Bookings
 * 
 * APIs for managing consultation bookings
 */
class BookingController extends Controller
{
    /**
     * Get my bookings
     * 
     * Get list of all bookings for authenticated student.
     * 
     * @authenticated
     * 
     * @queryParam status string Filter by status. Example: confirmed
     * @queryParam type string Filter by type. Example: consultation
     * @queryParam time string Filter by time (upcoming, past). Example: upcoming
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->bookings()
            ->with(['instructor.user']);

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by time
        if ($request->has('time')) {
            if ($request->time === 'upcoming') {
                $query->upcoming();
            } elseif ($request->time === 'past') {
                $query->past();
            }
        }

        $bookings = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Check availability
     * 
     * Check if a specific time slot is available.
     * 
     * @authenticated
     * 
     * @bodyParam instructor_id integer required Instructor ID. Example: 1
     * @bodyParam date date required Booking date. Example: 2024-03-15
     * @bodyParam start_time time required Start time. Example: 10:00
     * @bodyParam duration_minutes integer required Duration. Example: 60
     */
   
public function checkAvailability(Request $request): JsonResponse
{
    // 1. Validation
    try {
        $request->validate([
            'instructor_id'    => 'required|exists:instructors,id',
            'date'             => 'required|date|after_or_equal:today',
            'start_time'       => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|min:30|max:120',
        ], [
            'instructor_id.required'    => 'معرف المحاضر مطلوب',
            'instructor_id.exists'      => 'المحاضر المحدد غير موجود',
            'date.required'             => 'التاريخ مطلوب',
            'date.date'                 => 'صيغة التاريخ غير صحيحة',
            'date.after_or_equal'       => 'يجب أن يكون التاريخ اليوم أو في المستقبل',
            'start_time.required'       => 'وقت البدء مطلوب',
            'start_time.date_format'    => 'يجب أن يكون وقت البدء بصيغة HH:MM',
            'duration_minutes.required' => 'مدة الجلسة مطلوبة',
            'duration_minutes.integer'  => 'يجب أن تكون المدة عدداً صحيحاً',
            'duration_minutes.min'      => 'يجب أن تكون المدة 30 دقيقة على الأقل',
            'duration_minutes.max'      => 'يجب ألا تتجاوز المدة 120 دقيقة',
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'success'   => false,
            'available' => false,
            'message'   => 'بيانات غير صالحة',
            'errors'    => $e->errors(),
        ], 422);
    }

    // 2. Get instructor
    $instructor = Instructor::findOrFail($request->instructor_id);

    // 3. Check allowed days
    $dayOfWeek = strtolower(date('l', strtotime($request->date)));

    $arabicDays = [
        'sunday'    => 'الأحد',
        'monday'    => 'الاثنين',
        'tuesday'   => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday'  => 'الخميس',
        'friday'    => 'الجمعة',
        'saturday'  => 'السبت',
    ];

    $allowedDays = $instructor->availabilitySchedules()
        ->where('is_active', 1)
        ->pluck('day_of_week')
        ->toArray();

    if (!in_array($dayOfWeek, $allowedDays)) {
        $availableDaysInArabic = implode('، ', array_map(fn($day) => $arabicDays[$day] ?? $day, $allowedDays));
        return response()->json([
            'success'   => false,
            'available' => false,
            'message'   => "المحاضر غير متاح في هذا اليوم، الأيام المتاحة هي: {$availableDaysInArabic}",
        ], 422);
    }

    // 4. Get schedule for that specific day
    $schedule      = $instructor->availabilitySchedules()->where('day_of_week', $dayOfWeek)->where('is_active', 1)->first();
    $scheduleStart = substr($schedule->start_time, 0, 5);
    $scheduleEnd   = substr($schedule->end_time, 0, 5);

    // 5. Check start_time is within schedule range
    if ($request->start_time < $scheduleStart || $request->start_time >= $scheduleEnd) {
        return response()->json([
            'success'   => false,
            'available' => false,
            'message'   => "وقت البدء يجب أن يكون بين {$scheduleStart} و {$scheduleEnd}",
        ], 422);
    }

    // 6. Calculate end time and check it doesn't exceed schedule end
    $datetime = new \DateTime($request->date . ' ' . $request->start_time);
    $endTime  = clone $datetime;
    $endTime->modify("+{$request->duration_minutes} minutes");

    if ($endTime->format('H:i') > $scheduleEnd) {
        return response()->json([
            'success'   => false,
            'available' => false,
            'message'   => "وقت الانتهاء يجب أن لا يتجاوز {$scheduleEnd}",
        ], 422);
    }

    // 7. ✅ Check blocked slots with correct overlap logic
    $isBlocked = \App\Models\BlockedSlot::where('instructor_id', $instructor->id)
        ->where('date', $request->date)
        ->where(function ($query) use ($request, $endTime) {
            $query->whereNull('start_time')
                  ->orWhere(function ($q) use ($request, $endTime) {
                      $q->where('start_time', '<', $endTime->format('H:i:s'))
                        ->where('end_time', '>', $request->start_time . ':00');
                  });
        })
        ->first();

    if ($isBlocked) {
        return response()->json([
            'success'   => false,
            'available' => false,
            'message'   => $isBlocked->reason
                ? "هذا الوقت غير متاح بسبب: {$isBlocked->reason}"
                : 'هذا الوقت غير متاح',
        ], 422);
    }

    // 8. Check if slot is not already booked
    $isBooked = $instructor->bookings()
        ->where('booking_date', $request->date)
        ->where('start_time', '<', $endTime->format('H:i:s'))
        ->where('end_time', '>', $request->start_time)
        ->whereIn('status', ['confirmed', 'pending'])
        ->exists();

    if ($isBooked) {
        return response()->json([
            'success'   => false,
            'available' => false,
            'message'   => 'هذا الموعد محجوز بالفعل',
        ], 422);
    }

    // 9. All checks passed
    return response()->json([
        'success'   => true,
        'available' => true,
        'message'   => 'الموعد متاح',
        'data'      => [
            'instructor_id'    => $instructor->id,
            'instructor_name'  => $instructor->user->name,
            'date'             => $request->date,
            'day_of_week'      => $arabicDays[$dayOfWeek],
            'start_time'       => $request->start_time,
            'end_time'         => $endTime->format('H:i'),
            'duration_minutes' => $request->duration_minutes,
            'price'            => (float) $instructor->consultation_price,
        ],
    ]);

}
    /**
     * Create booking
     * 
     * Book a consultation with an instructor.
     * 
     * @authenticated
     * 
     * @bodyParam instructor_id integer required Instructor ID. Example: 1
     * @bodyParam type string Booking type. Example: consultation
     * @bodyParam date date required Booking date. Example: 2024-03-15
     * @bodyParam start_time time required Start time. Example: 10:00
     * @bodyParam duration_minutes integer required Duration. Example: 60
     * @bodyParam meeting_type string Meeting type (online, in_person). Example: online
     * @bodyParam notes text Optional notes. Example: أريد استشارة في مجال القيادة
     */
   public function create(Request $request): JsonResponse
{
    try {
        $request->validate([
            'instructor_id'    => 'required|exists:instructors,id',
            'type'             => 'sometimes|in:consultation,workshop,private_session',
            'date'             => 'required|date|after_or_equal:today',
            'start_time'       => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|min:30|max:120',
            'meeting_type'     => 'required|in:online,in_person',
            'notes'            => 'sometimes|string|max:500',
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'بيانات غير صالحة',
            'errors'  => $e->errors(),
        ], 422);
    }

    $user       = $request->user();
    $instructor = Instructor::findOrFail($request->instructor_id);

    // Calculate end time
    $datetime = new \DateTime($request->date . ' ' . $request->start_time);
    $endTime  = clone $datetime;
    $endTime->modify("+{$request->duration_minutes} minutes");



    // 1. Check if the requested day is in instructor's available days
$dayOfWeek = strtolower(date('l', strtotime($request->date)));

$arabicDays = [
    'sunday'    => 'الأحد',
    'monday'    => 'الاثنين',
    'tuesday'   => 'الثلاثاء',
    'wednesday' => 'الأربعاء',
    'thursday'  => 'الخميس',
    'friday'    => 'الجمعة',
    'saturday'  => 'السبت',
];

$allowedDays = $instructor->availabilitySchedules()
    ->where('is_active', 1)
    ->pluck('day_of_week')
    ->toArray();

if (!in_array($dayOfWeek, $allowedDays)) {
    $availableDaysInArabic = implode('، ', array_map(fn($day) => $arabicDays[$day] ?? $day, $allowedDays));
    return response()->json([
        'success' => false,
        'message' => "المحاضر غير متاح في هذا اليوم، الأيام المتاحة هي: {$availableDaysInArabic}",
    ], 422);
}
    // 1. Check blocked slots FIRST (break time, holiday, etc.)
    $isBlocked = \App\Models\BlockedSlot::where('instructor_id', $instructor->id)
        ->where('date', $request->date)
        ->where(function ($query) use ($request, $endTime) {
            $query->whereNull('start_time') // full day block
                  ->orWhere(function ($q) use ($request, $endTime) {
                      // Check overlap: blocked slot overlaps with requested slot
                      $q->where('start_time', '<', $endTime->format('H:i:s'))
                        ->where('end_time', '>', $request->start_time . ':00');
                  });
        })
        ->first();

    if ($isBlocked) {
        return response()->json([
            'success' => false,
            'message' => $isBlocked->reason
                ? "هذا الوقت غير متاح بسبب: {$isBlocked->reason}"
                : 'هذا الوقت غير متاح',
        ], 422);
    }

    // 2. Check general availability
    if (!$instructor->isAvailableAt($datetime)) {
        return response()->json([
            'success' => false,
            'message' => 'هذا الوقت غير متاح للحجز',
        ], 400);
    }

    // 3. Check if already booked
    $isBooked = $instructor->bookings()
        ->where('booking_date', $request->date)
        ->where('start_time', '<', $endTime->format('H:i:s'))
        ->where('end_time', '>', $request->start_time)
        ->whereIn('status', ['confirmed', 'pending'])
        ->exists();

    if ($isBooked) {
        return response()->json([
            'success' => false,
            'message' => 'هذا الموعد محجوز بالفعل',
        ], 400);
    }

    // 4. Create booking
    try {
        DB::beginTransaction();

        $booking = Booking::create([
            'user_id'          => $user->id,
            'instructor_id'    => $instructor->id,
            'type'             => $request->get('type', 'consultation'),
            'booking_date'     => $request->date,
            'start_time'       => $request->start_time,
            'end_time'         => $endTime->format('H:i'),
            'duration_minutes' => $request->duration_minutes,
            'meeting_type'     => $request->meeting_type,
            'price'            => $instructor->consultation_price,
            'payment_status'   => 'pending',
            'status'           => 'pending',
            'notes'            => $request->notes,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحجز بنجاح. يرجى إتمام عملية الدفع.',
            'data'    => new BookingResource($booking->load('instructor.user')),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الحجز',
            'debug'   => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
        ], 500);
    }
}

    /**
     * Get booking details
     * 
     * Get details of a specific booking.
     * 
     * @authenticated
     * 
     * @urlParam id integer required Booking ID. Example: 1
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $booking = $request->user()
            ->bookings()
            ->with(['instructor.user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Cancel booking
     * 
     * Cancel an existing booking.
     * 
     * @authenticated
     * 
     * @urlParam id integer required Booking ID. Example: 1
     * @bodyParam reason text Cancellation reason. Example: تغير الموعد
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'sometimes|string|max:500',
        ]);

        $booking = $request->user()
            ->bookings()
            ->findOrFail($id);

        if (!$booking->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا الحجز. يجب الإلغاء قبل 24 ساعة على الأقل.',
            ], 400);
        }

        $booking->cancel($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الحجز بنجاح',
            'data' => new BookingResource($booking->fresh()),
        ]);
    }
}