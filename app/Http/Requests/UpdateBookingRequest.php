<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_id' => 'sometimes|exists:leads,id',
            'appointment_time' => 'sometimes|date|after:now',
            'service' => 'sometimes|string|max:255',
            'duration_minutes' => 'nullable|integer|min:15|max:120',
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ];
    }
}