<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Appointment Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #ff9800; padding-bottom: 20px; }
        .header h1 { color: #ff9800; margin: 0; }
        .content { padding: 20px 0; }
        .info-box { background: #fff3e0; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ff9800; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #888; font-size: 12px; }
        .btn { display: inline-block; background: #ff9800; color: white; padding: 10px 25px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⏰ Appointment Reminder</h1>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $booking->lead->name ?? 'Valued Customer' }}</strong>,</p>
            <p>This is a reminder that you have an appointment in <strong>2 hours</strong>.</p>

            <div class="info-box">
                <p><strong>Service:</strong> {{ $booking->service ?? 'Consultation' }}</p>
                <p><strong>Date:</strong> {{ $booking->appointment_time->format('l, F j, Y') }}</p>
                <p><strong>Time:</strong> {{ $booking->appointment_time->format('g:i A') }}</p>
                <p><strong>Address:</strong> {{ $clinic['address'] ?? '123 Main St' }}</p>
                <p><strong>Phone:</strong> {{ $clinic['phone'] ?? '+1234567890' }}</p>
            </div>

            <p>Please arrive on time. If you need to reschedule, please contact us.</p>

            <p style="margin-top: 20px;">
                <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn">View Details</a>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $clinic['name'] ?? 'AI Receptionist' }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>