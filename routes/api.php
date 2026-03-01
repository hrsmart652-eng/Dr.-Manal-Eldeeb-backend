<?php




use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\CourseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
| Authentication: Laravel Passport (OAuth2)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Public Routes (No Authentication Required)
    |--------------------------------------------------------------------------
    */
    
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        // Registration & Login
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        
        // Password Reset (3-step process)
        Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
        Route::post('verify-reset-code', [PasswordResetController::class, 'verifyResetCode']);
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
        
        // Social Login (Optional - implement later)
        // Route::post('social/{provider}', [SocialAuthController::class, 'login']);
    });
    
    // Public Course Browsing
    Route::prefix('courses')->group(function () {
        Route::get('/', [CourseController::class, 'index']);
        Route::get('/{slug}', [CourseController::class, 'show']);
        Route::get('/{slug}/reviews', [CourseController::class, 'reviews']);
        Route::get('/{slug}/related', [CourseController::class, 'related']);
    });
    
    // Public Books Browsing
//     Route::prefix('books')->group(function () {
//         Route::get('/', [\App\Http\Controllers\Api\V1\BookController::class, 'index']);
//         Route::get('/{slug}', [\App\Http\Controllers\Api\V1\BookController::class, 'show']);
//         Route::get('/{slug}/reviews', [\App\Http\Controllers\Api\V1\BookController::class, 'reviews']);
//         Route::get('/{slug}/preview', [\App\Http\Controllers\Api\V1\BookController::class, 'preview']);
//     });
    
//     // Public Workshops
//     Route::prefix('workshops')->group(function () {
//         Route::get('/', [\App\Http\Controllers\Api\V1\WorkshopController::class, 'index']);
//         Route::get('/{id}', [\App\Http\Controllers\Api\V1\WorkshopController::class, 'show']);
//     });
    
//     // Instructors (Public)
//     Route::prefix('instructors')->group(function () {
//         Route::get('/', [\App\Http\Controllers\Api\V1\InstructorController::class, 'index']);
//         Route::get('/{id}', [\App\Http\Controllers\Api\V1\InstructorController::class, 'show']);
//         Route::get('/{id}/courses', [\App\Http\Controllers\Api\V1\InstructorController::class, 'courses']);
//         Route::get('/{id}/books', [\App\Http\Controllers\Api\V1\InstructorController::class, 'books']);
//         Route::get('/{id}/availability', [\App\Http\Controllers\Api\V1\InstructorController::class, 'availability']);
//     });
    
//     // Categories (Public)
//     Route::prefix('categories')->group(function () {
//         Route::get('/', [\App\Http\Controllers\Api\V1\CategoryController::class, 'index']);
//         Route::get('/{slug}', [\App\Http\Controllers\Api\V1\CategoryController::class, 'show']);
//         Route::get('/{slug}/courses', [\App\Http\Controllers\Api\V1\CategoryController::class, 'courses']);
//         Route::get('/{slug}/books', [\App\Http\Controllers\Api\V1\CategoryController::class, 'books']);
//     });
    
//     // Public Statistics
//     Route::get('statistics', [\App\Http\Controllers\Api\V1\StatisticsController::class, 'index']);
    
//     // Global Search
//     Route::get('search', [\App\Http\Controllers\Api\V1\SearchController::class, 'search']);
    
//     // Contact Form (Public)
//     Route::post('contact', [\App\Http\Controllers\Api\V1\ContactController::class, 'submit']);
    
//     /*
//     |--------------------------------------------------------------------------
//     | Protected Routes (Passport Authentication Required)
//     | Middleware: auth:api
//     |--------------------------------------------------------------------------
//     */
    
//     Route::middleware('auth:api')->group(function () {
        
//         // Authentication (Protected)
//         Route::prefix('auth')->group(function () {
//             Route::post('logout', [AuthController::class, 'logout']);
//             Route::get('me', [AuthController::class, 'me']);
//             Route::put('profile', [AuthController::class, 'updateProfile']);
//             Route::post('avatar', [AuthController::class, 'uploadAvatar']);
//             Route::put('password', [AuthController::class, 'changePassword']);
//             Route::post('verify-email', [AuthController::class, 'verifyEmail']);
//             Route::post('resend-verification', [AuthController::class, 'resendVerification']);
//         });
        
//         /*
//         |--------------------------------------------------------------------------
//         | Student Routes
//         | Scope: student
//         |--------------------------------------------------------------------------
//         */
        
//         Route::prefix('student')->middleware('scope:student,admin')->group(function () {
            
//             // Dashboard
//             Route::get('dashboard', [\App\Http\Controllers\Api\V1\Student\DashboardController::class, 'index']);
            
//             // Profile Management
//             Route::prefix('profile')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Student\ProfileController::class, 'show']);
//                 Route::put('/', [\App\Http\Controllers\Api\V1\Student\ProfileController::class, 'update']);
//                 Route::post('avatar', [\App\Http\Controllers\Api\V1\Student\ProfileController::class, 'uploadAvatar']);
//                 Route::delete('avatar', [\App\Http\Controllers\Api\V1\Student\ProfileController::class, 'deleteAvatar']);
//             });
            
//             // My Courses
//             Route::prefix('courses')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Student\CourseController::class, 'index']);
//                 Route::get('/{courseId}', [\App\Http\Controllers\Api\V1\Student\CourseController::class, 'show']);
//                 Route::post('/{courseId}/enroll', [\App\Http\Controllers\Api\V1\Student\CourseController::class, 'enroll']);
//                 Route::get('/{courseId}/progress', [\App\Http\Controllers\Api\V1\Student\CourseController::class, 'progress']);
//                 Route::put('/{courseId}/lectures/{lectureId}/complete', [\App\Http\Controllers\Api\V1\Student\CourseController::class, 'markLectureComplete']);
                
//                 // Reviews
//                 Route::post('/{courseId}/review', [\App\Http\Controllers\Api\V1\Student\ReviewController::class, 'storeCourseReview']);
//                 Route::put('/{courseId}/review', [\App\Http\Controllers\Api\V1\Student\ReviewController::class, 'updateCourseReview']);
//                 Route::delete('/{courseId}/review', [\App\Http\Controllers\Api\V1\Student\ReviewController::class, 'deleteCourseReview']);
//             });
            
//             // My Books
//             Route::prefix('books')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Student\BookController::class, 'index']);
//                 Route::post('/{bookId}/purchase', [\App\Http\Controllers\Api\V1\Student\BookController::class, 'purchase']);
//                 Route::get('/{bookId}/download', [\App\Http\Controllers\Api\V1\Student\BookController::class, 'download']);
                
//                 // Reviews
//                 Route::post('/{bookId}/review', [\App\Http\Controllers\Api\V1\Student\ReviewController::class, 'storeBookReview']);
//                 Route::put('/{bookId}/review', [\App\Http\Controllers\Api\V1\Student\ReviewController::class, 'updateBookReview']);
//                 Route::delete('/{bookId}/review', [\App\Http\Controllers\Api\V1\Student\ReviewController::class, 'deleteBookReview']);
//             });
            
//             // Workshops
//             Route::prefix('workshops')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Student\WorkshopController::class, 'index']);
//                 Route::post('/{workshopId}/enroll', [\App\Http\Controllers\Api\V1\Student\WorkshopController::class, 'enroll']);
//             });
            
//             // Bookings/Consultations
//             Route::prefix('bookings')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Student\BookingController::class, 'index']);
//                 Route::post('/check-availability', [\App\Http\Controllers\Api\V1\Student\BookingController::class, 'checkAvailability']);
//                 Route::post('/', [\App\Http\Controllers\Api\V1\Student\BookingController::class, 'create']);
//                 Route::get('/{id}', [\App\Http\Controllers\Api\V1\Student\BookingController::class, 'show']);
//                 Route::put('/{id}/cancel', [\App\Http\Controllers\Api\V1\Student\BookingController::class, 'cancel']);
//             });
            
//             // Certificates
//             Route::prefix('certificates')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Student\CertificateController::class, 'index']);
//                 Route::get('/{id}', [\App\Http\Controllers\Api\V1\Student\CertificateController::class, 'show']);
//                 Route::get('/{id}/download', [\App\Http\Controllers\Api\V1\Student\CertificateController::class, 'download']);
//                 Route::get('/{id}/verify', [\App\Http\Controllers\Api\V1\Student\CertificateController::class, 'verify']);
//             });
            
//             // Wishlist
//             Route::prefix('wishlist')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Student\WishlistController::class, 'index']);
//                 Route::post('/{courseId}', [\App\Http\Controllers\Api\V1\Student\WishlistController::class, 'add']);
//                 Route::delete('/{courseId}', [\App\Http\Controllers\Api\V1\Student\WishlistController::class, 'remove']);
//             });
            
//             // Transactions & Orders
//             Route::prefix('transactions')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Student\TransactionController::class, 'index']);
//                 Route::get('/{id}', [\App\Http\Controllers\Api\V1\Student\TransactionController::class, 'show']);
//                 Route::get('/{id}/invoice', [\App\Http\Controllers\Api\V1\Student\TransactionController::class, 'downloadInvoice']);
//             });
//         });
        
//         /*
//         |--------------------------------------------------------------------------
//         | Instructor Routes
//         | Scope: instructor
//         |--------------------------------------------------------------------------
//         */
        
//         Route::prefix('instructor')->middleware('scope:instructor,admin')->group(function () {
            
//             // Dashboard
//             Route::get('dashboard', [\App\Http\Controllers\Api\V1\Instructor\DashboardController::class, 'index']);
            
//             // Manage Courses
//             Route::prefix('courses')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Instructor\CourseController::class, 'index']);
//                 Route::post('/', [\App\Http\Controllers\Api\V1\Instructor\CourseController::class, 'store']);
//                 Route::get('/{id}', [\App\Http\Controllers\Api\V1\Instructor\CourseController::class, 'show']);
//                 Route::put('/{id}', [\App\Http\Controllers\Api\V1\Instructor\CourseController::class, 'update']);
//                 Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Instructor\CourseController::class, 'destroy']);
//                 Route::post('/{id}/publish', [\App\Http\Controllers\Api\V1\Instructor\CourseController::class, 'publish']);
                
//                 // Students
//                 Route::get('/{id}/students', [\App\Http\Controllers\Api\V1\Instructor\CourseController::class, 'students']);
//             });
            
//             // Availability Schedule
//             Route::prefix('availability')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Instructor\AvailabilityController::class, 'index']);
//                 Route::post('/', [\App\Http\Controllers\Api\V1\Instructor\AvailabilityController::class, 'store']);
//                 Route::put('/{id}', [\App\Http\Controllers\Api\V1\Instructor\AvailabilityController::class, 'update']);
//                 Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Instructor\AvailabilityController::class, 'destroy']);
//                 Route::post('/block', [\App\Http\Controllers\Api\V1\Instructor\AvailabilityController::class, 'blockSlot']);
//             });
            
//             // Bookings Management
//             Route::prefix('bookings')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Instructor\BookingController::class, 'index']);
//                 Route::get('/{id}', [\App\Http\Controllers\Api\V1\Instructor\BookingController::class, 'show']);
//                 Route::put('/{id}/confirm', [\App\Http\Controllers\Api\V1\Instructor\BookingController::class, 'confirm']);
//                 Route::put('/{id}/cancel', [\App\Http\Controllers\Api\V1\Instructor\BookingController::class, 'cancel']);
//                 Route::put('/{id}/complete', [\App\Http\Controllers\Api\V1\Instructor\BookingController::class, 'markComplete']);
//             });
            
//             // Analytics
//             Route::get('analytics', [\App\Http\Controllers\Api\V1\Instructor\AnalyticsController::class, 'index']);
//             Route::get('analytics/revenue', [\App\Http\Controllers\Api\V1\Instructor\AnalyticsController::class, 'revenue']);
//         });
        
//         /*
//         |--------------------------------------------------------------------------
//         | Admin Routes
//         | Scope: admin
//         |--------------------------------------------------------------------------
//         */
        
//         Route::prefix('admin')->middleware('scope:admin')->group(function () {
            
//             // Dashboard Statistics
//             Route::get('dashboard/stats', [\App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'stats']);
//             Route::get('dashboard/analytics', [\App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'analytics']);
            
//             // User Management
//             Route::prefix('users')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'index']);
//                 Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'show']);
//                 Route::post('/', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'store']);
//                 Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'update']);
//                 Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'destroy']);
//                 Route::put('/{id}/activate', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'activate']);
//                 Route::put('/{id}/deactivate', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'deactivate']);
//             });
            
//             // Course Management
//             Route::prefix('courses')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Admin\CourseController::class, 'index']);
//                 Route::post('/', [\App\Http\Controllers\Api\V1\Admin\CourseController::class, 'store']);
//                 Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\CourseController::class, 'show']);
//                 Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\CourseController::class, 'update']);
//                 Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\CourseController::class, 'destroy']);
//                 Route::post('/{id}/publish', [\App\Http\Controllers\Api\V1\Admin\CourseController::class, 'publish']);
//                 Route::post('/{id}/unpublish', [\App\Http\Controllers\Api\V1\Admin\CourseController::class, 'unpublish']);
//             });
            
//             // Book Management
//             Route::apiResource('books', \App\Http\Controllers\Api\V1\Admin\BookController::class);
            
//             // Workshop Management
//             Route::apiResource('workshops', \App\Http\Controllers\Api\V1\Admin\WorkshopController::class);
            
//             // Category Management
//             Route::apiResource('categories', \App\Http\Controllers\Api\V1\Admin\CategoryController::class);
            
//             // Review Moderation
//             Route::prefix('reviews')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Admin\ReviewController::class, 'index']);
//                 Route::get('/pending', [\App\Http\Controllers\Api\V1\Admin\ReviewController::class, 'pending']);
//                 Route::post('/{id}/approve', [\App\Http\Controllers\Api\V1\Admin\ReviewController::class, 'approve']);
//                 Route::post('/{id}/reject', [\App\Http\Controllers\Api\V1\Admin\ReviewController::class, 'reject']);
//                 Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\ReviewController::class, 'destroy']);
//             });
            
//             // Contact Messages
//             Route::prefix('contact-messages')->group(function () {
//                 Route::get('/', [\App\Http\Controllers\Api\V1\Admin\ContactMessageController::class, 'index']);
//                 Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\ContactMessageController::class, 'show']);
//                 Route::put('/{id}/reply', [\App\Http\Controllers\Api\V1\Admin\ContactMessageController::class, 'reply']);
//                 Route::put('/{id}/close', [\App\Http\Controllers\Api\V1\Admin\ContactMessageController::class, 'close']);
//             });
            
//             // Reports
//             Route::prefix('reports')->group(function () {
//                 Route::get('/revenue', [\App\Http\Controllers\Api\V1\Admin\ReportController::class, 'revenue']);
//                 Route::get('/enrollments', [\App\Http\Controllers\Api\V1\Admin\ReportController::class, 'enrollments']);
//                 Route::get('/students', [\App\Http\Controllers\Api\V1\Admin\ReportController::class, 'students']);
//                 Route::get('/courses', [\App\Http\Controllers\Api\V1\Admin\ReportController::class, 'courses']);
//             });
//         });
        
//         /*
//         |--------------------------------------------------------------------------
//         | Payment Routes
//         |--------------------------------------------------------------------------
//         */
        
//         Route::prefix('payments')->group(function () {
//             // Initiate payment
//             Route::post('/checkout', [\App\Http\Controllers\Api\V1\PaymentController::class, 'checkout']);
//             Route::post('/verify', [\App\Http\Controllers\Api\V1\PaymentController::class, 'verify']);
            
//             // Payment methods
//             Route::get('/methods', [\App\Http\Controllers\Api\V1\PaymentController::class, 'methods']);
//         });
//     });
    
//     /*
//     |--------------------------------------------------------------------------
//     | Payment Webhooks (No Authentication)
//     | These are called by payment gateways
//     |--------------------------------------------------------------------------
//     */
    
//     Route::prefix('webhooks')->group(function () {
//         Route::post('/paypal', [\App\Http\Controllers\Api\V1\Webhook\PayPalWebhookController::class, 'handle']);
//         Route::post('/stripe', [\App\Http\Controllers\Api\V1\Webhook\StripeWebhookController::class, 'handle']);
//     });
// });

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found. Please check the API documentation.',
    ], 404);
});

});