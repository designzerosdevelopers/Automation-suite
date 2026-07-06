@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
<form method="POST" action="{{ route('password.store') }}">
    @csrf
    
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    
    <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email"
               class="form-control @error('email') is-invalid @enderror"
               name="email" value="{{ old('email', $request->email) }}" required autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password"
               class="form-control @error('password') is-invalid @enderror"
               name="password" required autocomplete="new-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input id="password_confirmation" type="password"
               class="form-control @error('password_confirmation') is-invalid @enderror"
               name="password_confirmation" required autocomplete="new-password">
        @error('password_confirmation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary">
            Reset Password
        </button>
    </div>
</form>
@endsection