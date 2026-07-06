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
    Route::post('/resync-calendly', [BookingApiController::class, 'resyncCalendly']);
    Route::get('/booking/{id}', [BookingApiController::class, 'getBooking']);
    Route::get('/upcoming-bookings', [BookingApiController::class, 'getUpcomingBookings']);
    Route::get('/booking-stats', [BookingApiController::class, 'getStats']);
    
// });