@extends('layouts.guest')

@section('title', 'Log in to AutoCaller')

@section('content')
@if (session('status'))
    <div class="alert alert-success mb-3">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf
    
    <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
            name="email" value="{{ old('email') }}" required autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password"
            class="form-control @error('password') is-invalid @enderror" name="password" required>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-2">
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-decoration-none small">
                Forgot your password?
            </a>
        @endif
        <button type="submit" class="btn btn-primary">Log in</button>
    </div>
    
    @if (Route::has('register'))
        <div class="text-center mt-3">
            <a href="{{ route('register') }}" class="text-decoration-none">
                Don't have an account? Register
            </a>
        </div>
    @endif
</form>
@endsection