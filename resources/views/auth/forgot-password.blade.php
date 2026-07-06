@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
<p class="text-muted mb-4">
    Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
</p>

@if (session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    
    <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email"
               class="form-control @error('email') is-invalid @enderror"
               name="email" value="{{ old('email') }}" required autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary">
            Email Password Reset Link
        </button>
    </div>
</form>
@endsection