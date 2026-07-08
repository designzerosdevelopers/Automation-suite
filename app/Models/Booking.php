<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const MAX_CANCELLATIONS_PER_DAY = 3;
    const MAX_BOOKINGS_PER_DAY = 5;
    const CANCELLATION_COOLDOWN_MINUTES = 5;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'call_id',
        'google_event_id',
        'google_event_link',
        'google_meet_link',
        'google_sync_status',
        'google_sync_error',
        'appointment_time',
        'duration_minutes',
        'service',
        'status',
        'source_call_id',
        'reminder_2h_sent',
        'cancelled_at',
        'cancellation_reason',
        'notes',
        'metadata',
        'last_booking_at',
        'booking_count_today',
        'last_cancellation_at',
        'cancellation_count_today',
        'is_flagged',
    ];

    protected $casts = [
        'appointment_time' => 'datetime',
        'duration_minutes' => 'integer',
        'reminder_2h_sent' => 'boolean',
        'metadata' => 'array',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_booking_at' => 'datetime',
        'booking_count_today' => 'integer',
        'last_cancellation_at' => 'datetime',
        'cancellation_count_today' => 'integer',
        'is_flagged' => 'boolean',
    ];

    public function callLog()
    {
        return $this->belongsTo(CallLog::class, 'call_id', 'call_id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_time', '>=', now())
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->orderBy('appointment_time', 'asc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_time', today())
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->orderBy('appointment_time', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_SCHEDULED]);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeGoogleSynced($query)
    {
        return $query->where('google_sync_status', 'synced');
    }

    public function scopeGoogleFailed($query)
    {
        return $query->where('google_sync_status', 'failed');
    }

    public function getFormattedAppointmentAttribute(): ?string
    {
        return $this->appointment_time ? $this->appointment_time->format('l, F j, Y \a\t g:i A') : null;
    }

    public function getFormattedDateAttribute(): ?string
    {
        return $this->appointment_time ? $this->appointment_time->format('Y-m-d') : null;
    }

    public function getFormattedTimeAttribute(): ?string
    {
        return $this->appointment_time ? $this->appointment_time->format('g:i A') : null;
    }

    public function getIsGoogleSyncedAttribute(): bool
    {
        return $this->google_sync_status === 'synced' && !empty($this->google_event_id);
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_SCHEDULED]);
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_SCHEDULED]);
    }

    public function canBeCancelled(): bool
    {
        return $this->isActive() && $this->appointment_time->gt(now());
    }

    public function canBeRescheduled(): bool
    {
        return $this->isActive() && $this->appointment_time->gt(now());
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'last_cancellation_at' => now(),
        ]);
    }

    public function confirm(): void
    {
        $this->update(['status' => self::STATUS_CONFIRMED]);
    }

    public function complete(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function markGoogleSynced(string $eventId, string $eventLink, ?string $meetLink = null): void
    {
        $this->update([
            'google_event_id' => $eventId,
            'google_event_link' => $eventLink,
            'google_meet_link' => $meetLink,
            'google_sync_status' => 'synced',
            'google_sync_error' => null,
        ]);
    }

    public function markGoogleFailed(string $error): void
    {
        $this->update([
            'google_sync_status' => 'failed',
            'google_sync_error' => substr($error, 0, 500),
        ]);
    }

    public function getDurationInMinutes(): int
    {
        return $this->duration_minutes ?? config('ai-receptionist.booking.default_duration_minutes', 60);
    }

    public function getEndTime(): ?Carbon
    {
        return $this->appointment_time?->copy()->addMinutes($this->getDurationInMinutes());
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['formatted_appointment'] = $this->formatted_appointment;
        $data['formatted_date'] = $this->formatted_date;
        $data['formatted_time'] = $this->formatted_time;
        $data['is_google_synced'] = $this->is_google_synced;
        $data['is_active'] = $this->is_active;
        return $data;
    }
}