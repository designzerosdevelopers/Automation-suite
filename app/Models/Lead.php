<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone',
        'name',
        'email',
        'source',
        'status',
        'vapi_caller_id',
        'total_calls',
        'last_call_at',
        'booked_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_call_at' => 'datetime',
        'booked_at' => 'datetime',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function calls()
    {
        return $this->hasMany(CallLog::class);
    }

    public function latestCall()
    {
        return $this->hasOne(CallLog::class)->latest();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', '!=', 'lost');
    }
}