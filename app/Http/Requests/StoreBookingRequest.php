<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_id' => 'required|exists:leads,id',
            'appointment_time' => 'required|date|after:now',
            'service' => 'required|string|max:255',
            'duration_minutes' => 'nullable|integer|min:15|max:120',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'lead_id.required' => 'Please select a lead.',
            'lead_id.exists' => 'The selected lead does not exist.',
            'appointment_time.required' => 'Please select appointment time.',
            'appointment_time.after' => 'Appointment time must be in the future.',
            'service.required' => 'Please enter the service.',
        ];
    }
}