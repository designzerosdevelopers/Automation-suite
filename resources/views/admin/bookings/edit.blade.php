{{-- resources/views/admin/bookings/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-edit'></i> Edit Booking #{{ $booking->id }}
    </h1>
    <div class="subheader-actions">
        <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-primary">
            <i class="fal fa-eye mr-1"></i> View
        </a>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
            <i class="fal fa-arrow-left mr-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>Edit Booking</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $booking->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number *</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone', $booking->phone) }}" required>
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
                                           id="email" name="email" value="{{ old('email', $booking->email) }}">
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
                                        <option value="Consultation" {{ old('service', $booking->service) == 'Consultation' ? 'selected' : '' }}>Consultation</option>
                                        <option value="PRP" {{ old('service', $booking->service) == 'PRP' ? 'selected' : '' }}>PRP Treatment</option>
                                        <option value="Treatment" {{ old('service', $booking->service) == 'Treatment' ? 'selected' : '' }}>Treatment</option>
                                        <option value="Follow-up" {{ old('service', $booking->service) == 'Follow-up' ? 'selected' : '' }}>Follow-up</option>
                                        <option value="Other" {{ old('service', $booking->service) == 'Other' ? 'selected' : '' }}>Other</option>
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
                                           id="appointment_date" name="appointment_date" value="{{ old('appointment_date', $booking->appointment_time->format('Y-m-d')) }}" required>
                                    @error('appointment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="appointment_time">Time *</label>
                                    <input type="time" class="form-control @error('appointment_time') is-invalid @enderror" 
                                           id="appointment_time" name="appointment_time" value="{{ old('appointment_time', $booking->appointment_time->format('H:i')) }}" required>
                                    @error('appointment_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="duration_minutes">Duration (minutes)</label>
                                    <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" 
                                           id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', $booking->duration_minutes) }}" min="15" step="15">
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
                                        <option value="pending" {{ old('status', $booking->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="confirmed" {{ old('status', $booking->status) == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                        <option value="scheduled" {{ old('status', $booking->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                        <option value="completed" {{ old('status', $booking->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="cancelled" {{ old('status', $booking->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                                      id="notes" name="notes" rows="3">{{ old('notes', $booking->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fal fa-save mr-1"></i> Update Booking
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