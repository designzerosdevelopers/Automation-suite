@extends('layouts.app')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <i class='subheader-icon fal fa-chart-pie'></i> Dashboard
        </h1>
    </div>

    <div class="row">
        <div class="col-sm-6 col-xl-4">
            <div class="p-3 bg-success-300 rounded overflow-hidden position-relative text-white mb-g">
                <div>
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $stats['total_bookings'] ?? 0 }}
                        <small class="m-0 l-h-n">Total Bookings</small>
                    </h3>
                </div>
                <i class="fal fa-calendar-check position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="p-3 bg-info-300 rounded overflow-hidden position-relative text-white mb-g">
                <div>
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $stats['today_bookings'] ?? 0 }}
                        <small class="m-0 l-h-n">Today's Bookings</small>
                    </h3>
                </div>
                <i class="fal fa-calendar-day position-absolute pos-right pos-bottom opacity-15 mb-n5 mr-n6"
                    style="font-size: 8rem;"></i>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="p-3 bg-warning-400 rounded overflow-hidden position-relative text-white mb-g">
                <div>
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $stats['total_calls'] ?? 0 }}
                        <small class="m-0 l-h-n">Total Calls</small>
                    </h3>
                </div>
                <i class="fal fa-phone position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div id="panel-1" class="panel">
                <div class="panel-hdr">
                    <h2>Recent Bookings</h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentBookings ?? [] as $booking)
                                        <tr>
                                            <td>{{ $booking->name ?? 'Unknown' }}</td>
                                            <td>{{ $booking->service ?? 'Consultation' }}</td>
                                            <td>{{ $booking->appointment_time ? $booking->appointment_time->format('Y-m-d H:i') : '-' }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge {{ $booking->status == 'confirmed' ? 'badge-success' : ($booking->status == 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                                                    {{ $booking->status ?? 'pending' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No bookings found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div id="panel-2" class="panel">
                <div class="panel-hdr">
                    <h2>Recent Calls</h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Call ID</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentCalls ?? [] as $call)
                                        <tr>
                                            <td>{{ $call['call_id'] ?? '-' }}</td>
                                            <td>{{ $call['lead']['name'] ?? 'Unknown' }}</td>
                                            <td>
                                                <span
                                                    class="badge {{ $call['status'] == 'completed' ? 'badge-success' : 'badge-warning' }}">
                                                    {{ $call['status'] ?? 'unknown' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">No calls found.</td>
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

    <div class="row">
        <div class="col-12">
            <div id="panel-3" class="panel">
                <div class="panel-hdr">
                    <h2>
                        <i class="fal fa-phone-alt"></i> Initiate Call
                    </h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Success!</strong> {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <form id="callForm" method="POST" action="{{ route('make.call') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label class="form-label fw-semibold">Full Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control form-control-lg"
                                            placeholder="e.g. John Doe" required value="{{ old('name') }}">
                                        @error('name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label class="form-label fw-semibold">Contact Number <span
                                                class="text-danger">*</span></label>
                                        <input type="tel" name="contact_number" class="form-control form-control-lg"
                                            placeholder="e.g. +1 234 567 8900" required
                                            value="{{ old('contact_number') }}">
                                        <small class="text-muted">Include the country code (e.g. +1 for USA, +971 for
                                            UAE).</small>
                                        @error('contact_number')
                                            <br><small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="form-group w-100">
                                        <button id="callButton" type="submit"
                                            class="btn btn-primary btn-lg w-100 fw-semibold">
                                            <i class="fal fa-phone"></i> Call Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div id="callMessage" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const callForm = document.getElementById('callForm');
            const callButton = document.getElementById('callButton');
            const callMessage = document.getElementById('callMessage');

            if (callForm) {
                callForm.addEventListener('submit', function(e) {
                    callButton.disabled = true;
                    callButton.innerHTML = '<i class="fal fa-spinner fa-spin"></i> Calling...';

                    callMessage.innerHTML = `
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fal fa-spinner fa-spin"></i> 
                        Initiating call... Please wait.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `;

                    setTimeout(function() {
                        if (!callButton.disabled) return;
                        const isSubmitting = callForm.dataset.submitting === 'true';
                        if (!isSubmitting) {
                            callButton.disabled = false;
                            callButton.innerHTML = '<i class="fal fa-phone"></i> Call Now';
                        }
                    }, 10000);
                });
            }

            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert-dismissible');
                alerts.forEach(function(alert) {
                    setTimeout(function() {
                        const closeBtn = alert.querySelector('.close');
                        if (closeBtn) {
                            closeBtn.click();
                        }
                    }, 5000);
                });
            }, 1000);
        });
    </script>
@endsection
