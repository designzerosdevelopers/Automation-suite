<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

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
    ];

    protected $casts = [
        'appointment_time' => 'datetime',
        'duration_minutes' => 'integer',
        'reminder_2h_sent' => 'boolean',
        'metadata' => 'array',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship with Call Log
    public function callLog()
    {
        return $this->belongsTo(CallLog::class, 'call_id', 'call_id');
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('appointment_time', 'asc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_time', today())
            ->where('status', '!=', 'cancelled')
            ->orderBy('appointment_time', 'asc');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeGoogleSynced($query)
    {
        return $query->where('google_sync_status', 'synced');
    }

    public function scopeGoogleFailed($query)
    {
        return $query->where('google_sync_status', 'failed');
    }

    // Accessors
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

    // Status checks
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'scheduled']);
    }

    public function canBeRescheduled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'scheduled']) 
            && $this->appointment_time->gt(now());
    }

    // Actions
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
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
        return $data;
    }
}