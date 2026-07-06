<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, minimal-ui">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Theme CSS -->
    <link id="vendorsbundle" rel="stylesheet" media="screen, print" href="{{ asset('assets/admin/css/vendors.bundle.css') }}">
    <link id="appbundle" rel="stylesheet" media="screen, print" href="{{ asset('assets/admin/css/app.bundle.css') }}">
</head>
<body class="mod-bg-1">
    <div class="d-flex flex-column justify-content-center align-items-center min-vh-100">
        <div class="mb-4 text-center">
            <a href="{{ url('/') }}">
                <img src="{{ asset('logo.png') }}" alt="Logo" class="img-fluid" style="width: 80px; height: 80px;">
            </a>
        </div>
        
        <div class="card shadow-sm w-100" style="max-width: 400px;">
            <div class="card-body p-4">
                @isset($title)
                    <h4 class="card-title text-center mb-4">{{ $title }}</h4>
                @endisset
                
                {{-- Use @yield('content') instead of {{ $slot }} --}}
                @yield('content')
            </div>
        </div>
        
        <div class="mt-4 text-center text-muted small">
            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.
        </div>
    </div>
    
    <script src="{{ asset('assets/admin/js/vendors.bundle.js') }}"></script>
    <script src="{{ asset('assets/admin/js/app.bundle.js') }}"></script>
</body>
</html>