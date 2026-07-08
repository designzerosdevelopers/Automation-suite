<?php

namespace App\Repositories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingRepository
{
    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function update(Booking $booking, array $data): Booking
    {
        $booking->update($data);
        return $booking;
    }

    public function findById(int $id): ?Booking
    {
        return Booking::with(['callLog'])->find($id);
    }

    public function findByIdOrFail(int $id): Booking
    {
        return Booking::with(['callLog'])->findOrFail($id);
    }

    public function findByPhone(string $phone): ?Booking
    {
        return Booking::where('phone', $phone)
            ->active()
            ->orderBy('appointment_time', 'desc')
            ->first();
    }

    public function findByPhoneWithFormats(string $phone): ?Booking
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        
        return Booking::whereIn('status', ['pending', 'confirmed', 'scheduled'])
            ->where('appointment_time', '>=', now())
            ->where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->orderBy('appointment_time', 'asc')
            ->first();
    }

    public function getActiveBookingsByPhone(string $phone): Collection
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        
        return Booking::whereIn('status', ['pending', 'confirmed', 'scheduled'])
            ->where('appointment_time', '>=', now())
            ->where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->orderBy('appointment_time', 'asc')
            ->get();
    }

    public function getUpcomingByPhone(string $phone): Collection
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        
        return Booking::whereIn('status', ['pending', 'confirmed', 'scheduled'])
            ->where('appointment_time', '>=', now())
            ->where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->orderBy('appointment_time', 'asc')
            ->get();
    }

    public function findUpcomingByPhone(string $phone): ?Booking
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        
        return Booking::whereIn('status', ['pending', 'confirmed', 'scheduled'])
            ->where('appointment_time', '>=', now())
            ->where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->orderBy('appointment_time', 'asc')
            ->first();
    }

    public function isSlotAvailable(string $datetime): bool
    {
        $existingBooking = Booking::where('appointment_time', $datetime)
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->exists();

        return !$existingBooking;
    }

    public function isSlotAvailableForBooking(string $datetime, int $bookingId): bool
    {
        $existingBooking = Booking::where('appointment_time', $datetime)
            ->where('id', '!=', $bookingId)
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->exists();

        return !$existingBooking;
    }

    public function countBookingsToday(string $phone): int
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        $today = now()->startOfDay();
        $tomorrow = now()->endOfDay();
        
        return Booking::where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->whereBetween('created_at', [$today, $tomorrow])
            ->count();
    }

    public function countCancellationsToday(string $phone): int
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        $today = now()->startOfDay();
        $tomorrow = now()->endOfDay();
        
        return Booking::where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->where('status', Booking::STATUS_CANCELLED)
            ->whereBetween('cancelled_at', [$today, $tomorrow])
            ->count();
    }

    public function getLastCancellation(string $phone): ?Booking
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        
        return Booking::where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->where('status', Booking::STATUS_CANCELLED)
            ->orderBy('cancelled_at', 'desc')
            ->first();
    }

    public function isPhoneFlagged(string $phone): bool
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        
        $booking = Booking::where(function($query) use ($phoneFormats) {
            foreach ($phoneFormats as $format) {
                $query->orWhere('phone', $format);
            }
        })->first();
        
        return $booking ? $booking->is_flagged : false;
    }

    public function flagPhone(string $phone): void
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        
        Booking::where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->update(['is_flagged' => true]);
    }

    public function unflagPhone(string $phone): void
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        
        Booking::where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->update(['is_flagged' => false]);
    }

    public function incrementCancellationCountToday(Booking $booking): void
    {
        $booking->increment('cancellation_count_today');
        $booking->update(['last_cancellation_at' => now()]);
    }

    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        $booking->cancel($reason);
        return $booking;
    }

    public function delete(Booking $booking): bool
    {
        return $booking->delete();
    }

    public function getStats(): array
    {
        $total = Booking::count();
        $confirmed = Booking::confirmed()->count();
        $pending = Booking::pending()->count();
        $cancelled = Booking::cancelled()->count();
        $upcoming = Booking::upcoming()->count();
        $today = Booking::today()->count();
        $googleSynced = Booking::googleSynced()->count();
        $googleFailed = Booking::googleFailed()->count();
        $active = Booking::active()->count();
        
        return compact('total', 'confirmed', 'pending', 'cancelled', 'upcoming', 'today', 'googleSynced', 'googleFailed', 'active');
    }

    public function findByGoogleEventId(string $googleEventId): ?Booking
    {
        return Booking::where('google_event_id', $googleEventId)->first();
    }

    public function findByCallId(string $callId): ?Booking
    {
        return Booking::where('call_id', $callId)->first();
    }

    public function getUpcoming(): Collection
    {
        return Booking::with(['callLog'])->upcoming()->get();
    }

    public function getToday(): Collection
    {
        return Booking::with(['callLog'])->today()->get();
    }

    public function getPendingReminders(): Collection
    {
        return Booking::where('status', 'pending')
            ->where('reminder_2h_sent', false)
            ->where('appointment_time', '>=', now())
            ->get();
    }

    public function getGoogleFailed(): Collection
    {
        return Booking::where('google_sync_status', 'failed')
            ->whereNotNull('google_sync_error')
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();
    }

    public function getGoogleFailedOlderThan(int $days): Collection
    {
        return Booking::where('google_sync_status', 'failed')
            ->whereNotNull('google_sync_error')
            ->where('created_at', '<=', now()->subDays($days))
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();
    }

    public function getByPhone(string $phone): Collection
    {
        $phoneFormats = $this->getPhoneFormats($phone);
        
        return Booking::where(function($query) use ($phoneFormats) {
                foreach ($phoneFormats as $format) {
                    $query->orWhere('phone', $format);
                }
            })
            ->orderBy('appointment_time', 'desc')
            ->get();
    }

    public function getCountByStatus(): array
    {
        return Booking::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return Booking::whereBetween('appointment_time', [$startDate, $endDate])
            ->orderBy('appointment_time', 'asc')
            ->get();
    }

    public function getUpcomingWithGoogleFailed(): Collection
    {
        return Booking::upcoming()
            ->where('google_sync_status', 'failed')
            ->whereNotNull('google_sync_error')
            ->get();
    }

    public function updateGoogleSyncStatus(Booking $booking, string $status, ?string $error = null): Booking
    {
        $data = ['google_sync_status' => $status];
        
        if ($error !== null) {
            $data['google_sync_error'] = substr($error, 0, 500);
        }
        
        $booking->update($data);
        return $booking;
    }

    public function search(string $query): Collection
    {
        return Booking::where('name', 'LIKE', "%{$query}%")
            ->orWhere('phone', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('service', 'LIKE', "%{$query}%")
            ->orderBy('appointment_time', 'asc')
            ->get();
    }

    public function incrementBookingCountToday(Booking $booking): void
    {
        $booking->increment('booking_count_today');
        $booking->update(['last_booking_at' => now()]);
    }

    public function resetDailyCounts(): void
    {
        Booking::whereDate('created_at', '<', today())
            ->update([
                'booking_count_today' => 0,
                'cancellation_count_today' => 0,
            ]);
    }

    private function getPhoneFormats(string $phone): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        return array_unique([
            $phone,
            $cleanPhone,
            ltrim($cleanPhone, '92'),
            '0' . ltrim($cleanPhone, '92'),
            '+92' . ltrim($cleanPhone, '92'),
            '92' . ltrim($cleanPhone, '92'),
        ]);
    }
}