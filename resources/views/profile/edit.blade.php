@extends('layouts.app')

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-user-edit'></i> Profile Settings
    </h1>
</div>

<div class="row">
    <div class="col-lg-8 col-xl-6">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>Profile Information</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>
        
        <div id="panel-2" class="panel">
            <div class="panel-hdr">
                <h2>Update Password</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
        
        <div id="panel-3" class="panel">
            <div class="panel-hdr">
                <h2 class="text-danger">Delete Account</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection