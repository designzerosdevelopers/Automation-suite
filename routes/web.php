<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VapiCallController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\CallLogController;
use App\Http\Controllers\Api\VapiWebhookController;
use Illuminate\Support\Facades\Route;

// Route::get('/', fn() => redirect()->route('login'));

Route::get('/', function (\Illuminate\Http\Request $request) {
    try {
        $code = $request->get('code');
        if (!$code) {
            return "Error: No authorization code received.";
        }
        
        $service = app(\App\Services\Calendar\GoogleCalendarService::class);
        $service->authenticateWithCode($code);
        
        return "✅ Authentication successful! You can now close this window and test your calendar integration.";
    } catch (\Exception $e) {
        return "❌ Authentication failed: " . $e->getMessage();
    }
})->name('google.auth.callback');

// ===== DASHBOARD =====
Route::get('/dashboard', [AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ===== CALLS =====
Route::post('/make-call', [VapiCallController::class, 'handleCall'])
    ->middleware(['auth'])
    ->name('make.call');

// VAPI WEBHOOK
Route::post('/api/vapi/webhook', [VapiWebhookController::class, 'handle'])
    ->name('vapi.webhook');

// ===== PROFILE ROUTES =====
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ===== ADMIN APPROVALS =====
Route::middleware('auth')->group(function () {
    Route::get('/admin/approvals', [AdminController::class, 'index'])->name('admin.approvals');
    Route::post('/admin/approve/{user}', [AdminController::class, 'approveUser'])->name('admin.approve');
    Route::post('/admin/users/{user}/unapprove', [AdminController::class, 'unApproveUser'])->name('admin.unapprove');
});

// ===== ADMIN: CALL LOGS =====
Route::middleware(['auth'])->prefix('admin/call-logs')->name('admin.call-logs.')->group(function () {
    Route::get('/', [CallLogController::class, 'index'])->name('index');
    Route::get('/transcript/{callId}', [CallLogController::class, 'transcript'])->name('transcript');
    Route::get('/{callId}', [CallLogController::class, 'show'])->name('show');
});

// ===== ADMIN: LEADS =====
Route::middleware(['auth'])->prefix('admin/leads')->name('admin.leads.')->group(function () {
    Route::get('/', [LeadController::class, 'index'])->name('index');
    Route::get('/{lead}', [LeadController::class, 'show'])->name('show');
    Route::put('/{lead}', [LeadController::class, 'update'])->name('update');
    Route::post('/{lead}/note', [LeadController::class, 'addNote'])->name('note');
    Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('destroy');
    Route::get('/export', [LeadController::class, 'export'])->name('export');
});

// ===== ADMIN: BOOKINGS =====
Route::middleware(['auth'])->prefix('admin/bookings')->name('admin.bookings.')->group(function () {
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('/create', [BookingController::class, 'create'])->name('create');
    Route::post('/', [BookingController::class, 'store'])->name('store');
    Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
    Route::get('/{booking}/edit', [BookingController::class, 'edit'])->name('edit');
    Route::put('/{booking}', [BookingController::class, 'update'])->name('update');
    Route::delete('/{booking}', [BookingController::class, 'destroy'])->name('destroy');
    Route::post('/{booking}/confirm', [BookingController::class, 'confirm'])->name('confirm');
    Route::post('/{booking}/complete', [BookingController::class, 'complete'])->name('complete');
    Route::post('/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('reschedule');
});

// ===== GOOGLE OAUTH AUTHENTICATION =====
Route::get('/google-auth', function () {
    try {
        $service = app(\App\Services\Calendar\GoogleCalendarService::class);
        return redirect($service->getAuthUrl());
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
})->name('google.auth');



// ===== TEST ROUTES =====
Route::get('/test-json-path', function () {
    $path = env('GOOGLE_SERVICE_ACCOUNT_JSON');
    $fullPath = base_path($path);
    
    return [
        'env_value' => $path,
        'full_path' => $fullPath,
        'file_exists' => file_exists($fullPath),
        'file_size' => file_exists($fullPath) ? filesize($fullPath) : 'N/A',
        'is_readable' => file_exists($fullPath) ? is_readable($fullPath) : false,
    ];
});

Route::get('/test-google-calendar', function () {
    try {
        $service = app(\App\Services\Calendar\GoogleCalendarService::class);
        
        $result = $service->createBooking([
            'name' => 'Test Patient',
            'phone' => '+1-555-123-4567',
            'email' => 'test@example.com',
            'service' => 'Test Appointment',
            'appointment_time' => now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'notes' => 'This is a test booking via Google Calendar API',
            'location' => 'Phone Call',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Event created successfully!',
            'event_link' => $result['event_link'],
            'event_id' => $result['event_id'],
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

require __DIR__.'/auth.php';