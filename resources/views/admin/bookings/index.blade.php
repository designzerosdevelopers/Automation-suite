{{-- resources/views/admin/bookings/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-calendar-check'></i> Bookings
    </h1>
    {{-- <div class="subheader-actions">
        <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary">
            <i class="fal fa-plus mr-1"></i> New Booking
        </a>
    </div> --}}
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>Today's Appointments</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    @if($todayBookings->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>Google Sync</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todayBookings as $booking)
                                    <tr>
                                        <td>{{ $booking->formatted_time }}</td>
                                        <td>
                                            <a href="{{ route('admin.bookings.show', $booking->id) }}">
                                                {{ $booking->name }}
                                            </a>
                                        </td>
                                        <td>{{ $booking->phone }}</td>
                                        <td>{{ $booking->service }}</td>
                                        <td>
                                            <span class="badge {{ $booking->status == 'confirmed' ? 'badge-success' : ($booking->status == 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                                                {{ $booking->status }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($booking->is_google_synced)
                                                <i class="fal fa-check-circle text-success" title="Synced to Google Calendar"></i>
                                            @elseif($booking->google_sync_status == 'failed')
                                                <i class="fal fa-exclamation-circle text-danger" title="Sync failed"></i>
                                            @else
                                                <i class="fal fa-clock text-muted" title="Pending"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No appointments today.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-2" class="panel">
            <div class="panel-hdr">
                <h2>All Upcoming Bookings</h2>
                <div class="panel-toolbar">
                    <form method="GET" class="form-inline">
                        <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search..." value="{{ request('search') }}">
                        <select name="status" class="form-control form-control-sm mr-2">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </form>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped w-100">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Google Sync</th>
                                    <th>Call</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $booking)
                                <tr>
                                    <td>{{ $booking->formatted_date }}</td>
                                    <td>{{ $booking->formatted_time }}</td>
                                    <td>
                                        <a href="{{ route('admin.bookings.show', $booking->id) }}">
                                            {{ $booking->name }}
                                        </a>
                                    </td>
                                    <td>{{ $booking->phone }}</td>
                                    <td>{{ $booking->service }}</td>
                                    <td>
                                        <span class="badge {{ $booking->status == 'confirmed' ? 'badge-success' : ($booking->status == 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                                            {{ $booking->status }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($booking->is_google_synced)
                                            <i class="fal fa-check-circle text-success" title="Synced"></i>
                                        @elseif($booking->google_sync_status == 'failed')
                                            <i class="fal fa-exclamation-circle text-danger" title="Sync failed"></i>
                                        @else
                                            <i class="fal fa-clock text-muted" title="Pending"></i>
                                        @endif
                                    </td>
                                    <td>
                                        @if($booking->call_id)
                                            <a href="{{ route('admin.call-logs.show', $booking->call_id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fal fa-phone"></i> View Call
                                            </a>
                                        @else
                                            <span class="text-muted">No call</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fal fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-sm btn-info">
                                            <i class="fal fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="fal fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No bookings found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection