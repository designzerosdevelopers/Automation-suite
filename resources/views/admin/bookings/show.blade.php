{{-- resources/views/admin/bookings/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-calendar-check'></i> Booking Details
        <small>#{{ $booking->id }}</small>
    </h1>
    <div class="subheader-actions">
        <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-info">
            <i class="fal fa-edit mr-1"></i> Edit
        </a>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
            <i class="fal fa-arrow-left mr-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>Booking Information</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Name</dt>
                                <dd class="col-sm-8">{{ $booking->name }}</dd>

                                <dt class="col-sm-4">Phone</dt>
                                <dd class="col-sm-8">{{ $booking->phone }}</dd>

                                <dt class="col-sm-4">Email</dt>
                                <dd class="col-sm-8">{{ $booking->email ?? 'N/A' }}</dd>

                                <dt class="col-sm-4">Service</dt>
                                <dd class="col-sm-8">{{ $booking->service }}</dd>

                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8">
                                    <span class="badge {{ $booking->status == 'confirmed' ? 'badge-success' : ($booking->status == 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                                        {{ $booking->status }}
                                    </span>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Date</dt>
                                <dd class="col-sm-8">{{ $booking->formatted_date }}</dd>

                                <dt class="col-sm-4">Time</dt>
                                <dd class="col-sm-8">{{ $booking->formatted_time }}</dd>

                                <dt class="col-sm-4">Duration</dt>
                                <dd class="col-sm-8">{{ $booking->duration_minutes }} minutes</dd>

                                <dt class="col-sm-4">Created</dt>
                                <dd class="col-sm-8">{{ $booking->created_at->format('Y-m-d H:i') }}</dd>

                                <dt class="col-sm-4">Google Sync</dt>
                                <dd class="col-sm-8">
                                    @if($booking->is_google_synced)
                                        <span class="badge badge-success">Synced</span>
                                        @if($booking->google_event_link)
                                            <a href="{{ $booking->google_event_link }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                                <i class="fal fa-external-link"></i> View in Calendar
                                            </a>
                                        @endif
                                    @elseif($booking->google_sync_status == 'failed')
                                        <span class="badge badge-danger">Failed</span>
                                        <br><small class="text-danger">{{ $booking->google_sync_error }}</small>
                                    @else
                                        <span class="badge badge-warning">Pending</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>

                    @if($booking->notes)
                        <div class="row">
                            <div class="col-12">
                                <h6>Notes</h6>
                                <p>{{ $booking->notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Linked Call -->
        @if($booking->callLog)
        <div id="panel-2" class="panel">
            <div class="panel-hdr">
                <h2>Linked Call</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <dl class="row">
                        <dt class="col-sm-3">Call ID</dt>
                        <dd class="col-sm-9">{{ $booking->callLog->call_id ?? 'N/A' }}</dd>
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">{{ $booking->callLog->status ?? 'N/A' }}</dd>
                        <dt class="col-sm-3">Duration</dt>
                        <dd class="col-sm-9">{{ $booking->callLog->duration ?? 'N/A' }}</dd>
                        <dt class="col-sm-3">Created</dt>
                        <dd class="col-sm-9">{{ $booking->callLog->created_at ?? 'N/A' }}</dd>
                    </dl>
                    @if($booking->callLog && $booking->callLog->call_id)
                        <a href="{{ route('admin.call-logs.show', $booking->callLog->call_id) }}" class="btn btn-sm btn-primary">
                            <i class="fal fa-phone"></i> View Call Details
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-xl-4">
        <!-- Actions Panel -->
        <div id="panel-3" class="panel">
            <div class="panel-hdr">
                <h2>Actions</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    @if($booking->canBeCancelled())
                        <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST" class="mb-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                <i class="fal fa-times mr-1"></i> Cancel Booking
                            </button>
                        </form>
                    @endif

                    @if($booking->status == 'pending')
                        <form action="{{ route('admin.bookings.confirm', $booking->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fal fa-check mr-1"></i> Confirm Booking
                            </button>
                        </form>
                    @endif

                    @if($booking->status == 'confirmed')
                        <form action="{{ route('admin.bookings.complete', $booking->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-info w-100">
                                <i class="fal fa-check-double mr-1"></i> Mark as Completed
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-warning w-100 mb-2">
                        <i class="fal fa-edit mr-1"></i> Edit Booking
                    </a>

                    @if($booking->google_event_link)
                        <a href="{{ $booking->google_event_link }}" target="_blank" class="btn btn-outline-primary w-100">
                            <i class="fab fa-google mr-1"></i> View in Google Calendar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection