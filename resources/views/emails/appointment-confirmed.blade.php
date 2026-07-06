<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Appointment Confirmed</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #4CAF50; padding-bottom: 20px; }
        .header h1 { color: #4CAF50; margin: 0; }
        .content { padding: 20px 0; }
        .info-box { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info-box strong { display: inline-block; width: 120px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #888; font-size: 12px; }
        .btn { display: inline-block; background: #4CAF50; color: white; padding: 10px 25px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Appointment Confirmed</h1>
        </div>

        <div class="content">
            @if($isOwner)
                <p><strong>New Appointment Booked!</strong></p>
                <div class="info-box">
                    <p><strong>Customer:</strong> {{ $booking->lead->name ?? 'Unknown' }}</p>
                    <p><strong>Phone:</strong> {{ $booking->lead->phone ?? '-' }}</p>
                    <p><strong>Email:</strong> {{ $booking->lead->email ?? '-' }}</p>
                </div>
            @else
                <p>Hello <strong>{{ $booking->lead->name ?? 'Valued Customer' }}</strong>,</p>
                <p>Your appointment has been successfully confirmed!</p>
            @endif

            <div class="info-box">
                <p><strong>Service:</strong> {{ $booking->service ?? 'Consultation' }}</p>
                <p><strong>Date:</strong> {{ $booking->appointment_time->format('l, F j, Y') }}</p>
                <p><strong>Time:</strong> {{ $booking->appointment_time->format('g:i A') }}</p>
                <p><strong>Address:</strong> {{ $clinic['address'] ?? '123 Main St' }}</p>
                <p><strong>Phone:</strong> {{ $clinic['phone'] ?? '+1234567890' }}</p>
            </div>

            <p><strong>Confirmation Code:</strong> {{ $booking->confirmation_code }}</p>

            @if(!$isOwner)
                <p style="margin-top: 20px;">We look forward to seeing you!</p>
                <p><a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn">View Details</a></p>
            @else
                <p style="margin-top: 20px;">
                    <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn">View Booking</a>
                </p>
            @endif
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $clinic['name'] ?? 'AI Receptionist' }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>