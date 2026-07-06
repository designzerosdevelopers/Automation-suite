@extends('layouts.guest')

@section('title', 'Confirm Password')

@section('content')
<p class="text-muted mb-4">
    This is a secure area of the application. Please confirm your password before continuing.
</p>

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    
    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password"
               class="form-control @error('password') is-invalid @enderror"
               name="password" required autocomplete="current-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary">Confirm</button>
    </div>
</form>
@endsection