<section>
    <div class="mb-3">
        <h5 class="fw-semibold">{{ __('Profile Information') }}</h5>
        <p class="text-muted small">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </div>
    
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>
    
    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')
        
        <div class="form-group">
            <label for="name" class="form-label">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @if($errors->get('name'))
                <div class="text-danger small mt-1">
                    {{ $errors->first('name') }}
                </div>
            @endif
        </div>
        
        <div class="form-group">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @if($errors->get('email'))
                <div class="text-danger small mt-1">
                    {{ $errors->first('email') }}
                </div>
            @endif
            
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-muted small">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="btn btn-link p-0 align-baseline">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>
                    
                    @if (session('status') === 'verification-link-sent')
                        <p class="text-success small mt-1">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            
            @if (session('status') === 'profile-updated')
                <span class="text-muted small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>