<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\BookingRepository;
use App\Repositories\CallLogRepository;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    protected BookingRepository $bookingRepository;
    protected CallLogRepository $callLogRepository;

    public function __construct(
        BookingRepository $bookingRepository,
        CallLogRepository $callLogRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->callLogRepository = $callLogRepository;
    }

    public function index()
    {
        $stats = [
            'total_bookings' => count($this->bookingRepository->getUpcoming()),
            'today_bookings' => count($this->bookingRepository->getToday()),
            'total_calls' => count($this->callLogRepository->getRecent(1000)),
            'pending_bookings' => count($this->bookingRepository->getPendingReminders()),
        ];

        $recentBookings = $this->bookingRepository->getUpcoming();
        $recentCalls = $this->callLogRepository->getRecent(10);

        return view('admin.dashboard', compact('stats', 'recentBookings', 'recentCalls'));
    }

    public function stats()
    {
        return response()->json([
            'bookings' => [
                'total' => count($this->bookingRepository->getUpcoming()),
                'today' => count($this->bookingRepository->getToday()),
            ],
            'calls' => [
                'total' => count($this->callLogRepository->getRecent(1000)),
                'today' => count($this->callLogRepository->getRecent(100)),
            ],
        ]);
    }
}