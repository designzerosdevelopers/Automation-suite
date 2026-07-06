@extends('layouts.guest')

@section('title', 'Register')

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf
    
    <div class="form-group">
        <label for="name" class="form-label">Name</label>
        <input id="name" type="text"
               class="form-control @error('name') is-invalid @enderror"
               name="name" value="{{ old('name') }}" required autofocus>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email"
               class="form-control @error('email') is-invalid @enderror"
               name="email" value="{{ old('email') }}" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password"
               class="form-control @error('password') is-invalid @enderror"
               name="password" required>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input id="password_confirmation" type="password"
               class="form-control @error('password_confirmation') is-invalid @enderror"
               name="password_confirmation" required>
        @error('password_confirmation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="{{ route('login') }}" class="text-decoration-none small">
            Already registered?
        </a>
        <button type="submit" class="btn btn-primary">Register</button>
    </div>
</form>
@endsection