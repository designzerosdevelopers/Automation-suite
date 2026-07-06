<section>
    <div class="mb-3">
        <h5 class="fw-semibold text-danger">{{ __('Delete Account') }}</h5>
        <p class="text-muted small">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </div>
    
    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteAccountModal">
        {{ __('Delete Account') }}
    </button>
    
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Are you sure you want to delete your account?') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </p>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="{{ __('Password') }}" required>
                            @if($errors->userDeletion->has('password'))
                                <div class="text-danger small mt-1">
                                    {{ $errors->userDeletion->first('password') }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Delete Account') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>