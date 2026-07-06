<header class="page-header" role="banner">
    <div class="hidden-md-down dropdown-icon-menu position-relative">
        <a href="#" class="header-btn btn js-waves-off" data-action="toggle" data-class="nav-function-hidden" title="Hide Navigation">
            <i class="ni ni-menu"></i>
        </a>
    </div>
    
    <div class="hidden-lg-up">
        <a href="#" class="header-btn btn press-scale-down" data-action="toggle" data-class="mobile-nav-on">
            <i class="ni ni-menu"></i>
        </a>
    </div>
    
    <div class="ml-auto d-flex">
        <div>
            <a href="#" data-toggle="dropdown" class="header-icon d-flex align-items-center justify-content-center ml-2">
                <span class="profile-image rounded-circle d-inline-block" style="background-image:url('https://ui-avatars.com/api/?name={{ Auth::user()->name }}&background=886ab5&color=fff'); background-size: cover;"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-animated dropdown-lg">
                <div class="dropdown-header bg-trans-gradient d-flex flex-row py-4 rounded-top">
                    <div class="d-flex flex-row align-items-center mt-1 mb-1 color-white">
                        <span class="mr-2">
                            <span class="rounded-circle profile-image d-block" style="background-image:url('https://ui-avatars.com/api/?name={{ Auth::user()->name }}&background=886ab5&color=fff'); background-size: cover; width:45px; height:45px;"></span>
                        </span>
                        <div class="info-card-text">
                            <div class="fs-lg text-truncate text-truncate-lg text-white">{{ Auth::user()->name }}</div>
                            <span class="text-truncate text-truncate-md opacity-80">{{ Auth::user()->email }}</span>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider m-0"></div>
                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                    <span>Profile</span>
                </a>
                <div class="dropdown-divider m-0"></div>
                <a class="dropdown-item fw-500 pt-3 pb-3" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();">
                    <span>Logout</span>
                </a>
                <form id="logout-form-header" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</header>