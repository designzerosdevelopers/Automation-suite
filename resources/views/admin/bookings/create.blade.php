{{-- resources/views/admin/bookings/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-plus-circle'></i> New Booking
    </h1>
    <div class="subheader-actions">
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
            <i class="fal fa-arrow-left mr-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>Create New Booking</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form action="{{ route('admin.bookings.store') }}" method="POST">
                        @csrf

                        @if($callId)
                            <input type="hidden" name="call_id" value="{{ $callId }}">
                            <div class="alert alert-info">
                                <i class="fal fa-info-circle"></i> This booking is linked to call: <strong>{{ $callId }}</strong>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $selectedCall->customer_name ?? '') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number *</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone', $selectedCall->customer_number ?? '') }}" required>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email') }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="service">Service *</label>
                                    <select class="form-control @error('service') is-invalid @enderror" id="service" name="service" required>
                                        <option value="">Select Service</option>
                                        <option value="Consultation" {{ old('service') == 'Consultation' ? 'selected' : '' }}>Consultation</option>
                                        <option value="PRP" {{ old('service') == 'PRP' ? 'selected' : '' }}>PRP Treatment</option>
                                        <option value="Treatment" {{ old('service') == 'Treatment' ? 'selected' : '' }}>Treatment</option>
                                        <option value="Follow-up" {{ old('service') == 'Follow-up' ? 'selected' : '' }}>Follow-up</option>
                                        <option value="Other" {{ old('service') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('service')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="appointment_date">Date *</label>
                                    <input type="date" class="form-control @error('appointment_date') is-invalid @enderror" 
                                           id="appointment_date" name="appointment_date" value="{{ old('appointment_date', now()->addDay()->format('Y-m-d')) }}" required>
                                    @error('appointment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="appointment_time">Time *</label>
                                    <input type="time" class="form-control @error('appointment_time') is-invalid @enderror" 
                                           id="appointment_time" name="appointment_time" value="{{ old('appointment_time', '10:00') }}" required>
                                    @error('appointment_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="duration_minutes">Duration (minutes)</label>
                                    <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" 
                                           id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" min="15" step="15">
                                    @error('duration_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                                        <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fal fa-save mr-1"></i> Create Booking
                            </button>
                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection