<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, minimal-ui">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Theme CSS -->
    <link id="vendorsbundle" rel="stylesheet" media="screen, print" href="{{ asset('assets/admin/css/vendors.bundle.css') }}">
    <link id="appbundle" rel="stylesheet" media="screen, print" href="{{ asset('assets/admin/css/app.bundle.css') }}">
    <link id="myskin" rel="stylesheet" media="screen, print" href="{{ asset('assets/admin/css/skins/skin-master.css') }}">
    
    <style>
        /* Mobile Sidebar Fix */
        @media (max-width: 1199px) {
            .page-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 1050;
                width: 280px;
                overflow-y: auto;
                background: #fff;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
            }
            
            .mobile-nav-on .page-sidebar {
                transform: translateX(0);
            }
            
            .page-content-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1049;
            }
            
            .mobile-nav-on .page-content-overlay {
                display: block;
            }
        }
    </style>
</head>
<body class="mod-bg-1 mod-nav-link">
    <div class="page-wrapper">
        <div class="page-inner">
            <!-- Sidebar -->
            @include('layouts.sidebar')
            
            <!-- Mobile Overlay -->
            <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
            
            <div class="page-content-wrapper">
                <!-- Top Navigation -->
                @include('layouts.navigation')
                
                <!-- Page Content -->
                <main id="js-page-content" role="main" class="page-content">
                    @yield('content')
                </main>
                
                <!-- Footer -->
                @include('layouts.footer')
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="{{ asset('assets/admin/js/vendors.bundle.js') }}"></script>
    <script src="{{ asset('assets/admin/js/app.bundle.js') }}"></script>
    
    <!-- Mobile Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.querySelector('[data-action="toggle"][data-class="mobile-nav-on"]');
            const overlay = document.querySelector('.page-content-overlay');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('mobile-nav-on');
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    document.body.classList.remove('mobile-nav-on');
                });
            }
            
            // Close sidebar when clicking a link (optional)
            document.querySelectorAll('.page-sidebar .nav-link, .page-sidebar a').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1200) {
                        document.body.classList.remove('mobile-nav-on');
                    }
                });
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html>