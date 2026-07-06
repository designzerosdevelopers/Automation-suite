<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'channel',
        'sent_at',
        'delivered',
        'response',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}