<section>
    <div class="mb-3">
        <h5 class="fw-semibold">{{ __('Update Password') }}</h5>
        <p class="text-muted small">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </div>
    
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('put')
        
        <div class="form-group">
            <label for="update_password_current_password" class="form-label">{{ __('Current Password') }}</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" required>
            @if($errors->updatePassword->has('current_password'))
                <div class="text-danger small mt-1">
                    {{ $errors->updatePassword->first('current_password') }}
                </div>
            @endif
        </div>
        
        <div class="form-group">
            <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
            <input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password" required>
            @if($errors->updatePassword->has('password'))
                <div class="text-danger small mt-1">
                    {{ $errors->updatePassword->first('password') }}
                </div>
            @endif
        </div>
        
        <div class="form-group">
            <label for="update_password_password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required>
            @if($errors->updatePassword->has('password_confirmation'))
                <div class="text-danger small mt-1">
                    {{ $errors->updatePassword->first('password_confirmation') }}
                </div>
            @endif
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            
            @if (session('status') === 'password-updated')
                <span class="text-muted small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>