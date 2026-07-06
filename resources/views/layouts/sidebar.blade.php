<aside class="page-sidebar">
    <div class="page-logo">
        <a href="{{ route('dashboard') }}" class="page-logo-link press-scale-down d-flex align-items-center position-relative">
            <span class="page-logo-text mr-1">{{ config('app.name', 'Dashboard') }}</span>
        </a>
    </div>
    
    <nav id="js-primary-nav" class="primary-nav" role="navigation">
        <div class="nav-filter">
            <div class="position-relative">
                <input type="text" id="nav_filter_input" placeholder="Filter menu" class="form-control" tabindex="0">
            </div>
        </div>
        
        <ul id="js-nav-menu" class="nav-menu">
            <li class="{{ request()->routeIs('dashboard') ? 'active open' : '' }}">
                <a href="{{ route('dashboard') }}" title="Dashboard">
                    <i class="fal fa-home"></i>
                    <span class="nav-link-text">Dashboard</span>
                </a>
            </li>
            
            
            <li class="{{ request()->routeIs('admin.bookings.*') ? 'active open' : '' }}">
                <a href="{{ route('admin.bookings.index') }}" title="Bookings">
                    <i class="fal fa-calendar-check"></i>
                    <span class="nav-link-text">Bookings</span>
                </a>
            </li>
            
            @if(auth()->check() && auth()->user()->id === 1)
            <li class="{{ request()->routeIs('admin.call-logs.index') ? 'active open' : '' }}">
                <a href="{{ route('admin.call-logs.index') }}" title="Call Logs">
                    <i class="fal fa-phone"></i>
                    <span class="nav-link-text">Call Logs</span>
                </a>
            </li>
            
            <li class="{{ request()->routeIs('admin.approvals') ? 'active open' : '' }}">
                <a href="{{ route('admin.approvals') }}" title="Users">
                    <i class="fal fa-user-cog"></i>
                    <span class="nav-link-text">Users</span>
                </a>
            </li>
            @endif
        </ul>
    </nav>
    
    <div class="nav-footer shadow-top">
        <ul class="list-table m-auto nav-footer-buttons">
            <li>
                <a href="#" data-toggle="tooltip" data-placement="top" title="Profile">
                    <i class="fal fa-user"></i>
                </a>
            </li>
            <li>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" data-toggle="tooltip" data-placement="top" title="Logout">
                    <i class="fal fa-sign-out-alt"></i>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </li>
        </ul>
    </div>
</aside>