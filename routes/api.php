<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VapiWebhookController;
use App\Http\Controllers\Api\BookingApiController;

Route::post('/vapi/webhook', [VapiWebhookController::class, 'handle'])
    ->name('api.vapi.webhook');

// Route::middleware('verify.vapi')->group(function () {

Route::post('/check-availability', [BookingApiController::class, 'checkAvailability']);
Route::post('/book-appointment', [BookingApiController::class, 'bookAppointment']);
Route::post('/cancel-appointment', [BookingApiController::class, 'cancelAppointment']);
Route::post('/update-appointment', [BookingApiController::class, 'updateAppointment']);
Route::post('/booking-limits', [BookingApiController::class, 'getBookingLimits']);
Route::post('/upcoming-bookings', [BookingApiController::class, 'getUpcomingBookings']);
Route::get('/booking/{id}', [BookingApiController::class, 'getBooking']);
Route::get('/booking-stats', [BookingApiController::class, 'getStats']);
Route::post('/resync-google', [BookingApiController::class, 'resyncGoogle']);

    
// });