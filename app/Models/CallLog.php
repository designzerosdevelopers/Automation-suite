<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'lead_id',
        'booking_id',
        'direction',
        'status',
        'duration',
        'intent',
        'transcript',
        'summary',
        'metadata',
    ];

    protected $casts = [
        'transcript' => 'array',
        'metadata' => 'array',
        'duration' => 'integer',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}