<nav class="navbar">
    <a href="#" class="sidebar-toggler">
        <i data-feather="menu"></i>
    </a>
    <div class="navbar-content">
        <form class="search-form">
            <div class="input-group">
                <div class="input-group-text">
                    <i data-feather="search"></i>
                </div>
                <input type="text" class="form-control" id="navbarForm" placeholder="Search here...">
            </div>
        </form>
        <ul class="navbar-nav">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="appsDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i data-feather="grid"></i>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i data-feather="mail"></i>
                </a>
                <div class="p-0 dropdown-menu" aria-labelledby="messageDropdown">
                    <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                        <p>Messages will be available here</p>
                        <a href="javascript:;" class="text-muted">Clear all</a>
                    </div>
                    <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
                        <a href="javascript:;">View all</a>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i data-feather="bell"></i>
                    <div class="indicator">
                        <div class="circle"></div>
                    </div>
                </a>
                <div class="p-0 dropdown-menu" aria-labelledby="notificationDropdown">
                    <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                        <p>Notifications will be available here</p>
                        <a href="javascript:;" class="text-muted">Clear all</a>
                    </div>
                    <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
                        <a href="javascript:;">View all</a>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Hello, {{ $profileData->name }}
                </a>
                <div class="p-0 dropdown-menu" aria-labelledby="profileDropdown">
                    <div class="px-5 py-3 d-flex flex-column align-items-center border-bottom">
                        <div class="text-center">
                            <p class="tx-16 fw-bolder">{{ $profileData->name }}</p>
                            <p class="tx-12 text-muted">{{ $profileData->province }}</p>
                            <p class="tx-12 text-muted">{{ $profileData->center }}</p>
                            <p class="tx-12 text-muted">{{ $profileData->role }}</p>
                            <p class="tx-12 text-muted">{{ $profileData->email }}</p>
                        </div>
                    </div>
                    <ul class="p-1 list-unstyled">
                        {{-- <li class="py-2 dropdown-item">
                            <a href="{{ route('profile.edit') }}" class="text-body ms-0">
                                <i class="me-2 icon-md" data-feather="user"></i>
                                <span>Profile/Edit</span>
                            </a>
                        </li>
                        <li class="py-2 dropdown-item">
                            <a href="{{ route('profile.change-password') }}" class="text-body ms-0">
                                <i class="me-2 icon-md" data-feather="edit"></i>
                                <span>Change Password</span>
                            </a>
                        </li> --}}
                        <li class="py-2 dropdown-item">
                            <a href="{{ route('logout') }}" class="text-body ms-0"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="me-2 icon-md" data-feather="log-out"></i>
                                <span>Log Out</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</nav>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
