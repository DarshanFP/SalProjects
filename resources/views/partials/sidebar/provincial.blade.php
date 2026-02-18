{{-- Provincial sidebar nav items. Order: Dashboard → Projects → Reports → Team Management → Notifications → Settings → User Manual. Active state: explicit route matching per link. --}}

{{-- 1. Dashboard --}}
<li class="nav-item nav-category">Main</li>
<li class="nav-item {{ request()->routeIs('provincial.dashboard') ? 'active' : '' }}">
    <a href="{{ route('provincial.dashboard') }}" class="nav-link {{ request()->routeIs('provincial.dashboard') ? 'active' : '' }}">
        <i class="link-icon" data-feather="box"></i>
        <span class="link-title">Dashboard - Provincial</span>
    </a>
</li>

{{-- 2. Projects --}}
<li class="nav-item nav-category">Projects</li>
<li class="nav-item {{ request()->routeIs('provincial.projects.list') ? 'active' : '' }}">
    <a href="{{ route('provincial.projects.list', ['status' => 'submitted_to_provincial']) }}" class="nav-link {{ request()->routeIs('provincial.projects.list') ? 'active' : '' }}">
        <i class="link-icon" data-feather="folder"></i>
        <span class="link-title">Pending Projects</span>
    </a>
</li>
<li class="nav-item {{ request()->routeIs('provincial.approved.projects') ? 'active' : '' }}">
    <a href="{{ route('provincial.approved.projects') }}" class="nav-link {{ request()->routeIs('provincial.approved.projects') ? 'active' : '' }}">
        <i class="link-icon" data-feather="check-circle"></i>
        <span class="link-title">Approved Projects</span>
    </a>
</li>
<li class="nav-item {{ request()->routeIs('projects.trash.index') ? 'active' : '' }}">
    <a href="{{ route('projects.trash.index') }}" class="nav-link {{ request()->routeIs('projects.trash.index') ? 'active' : '' }}">
        <i class="link-icon" data-feather="trash-2"></i>
        <span class="link-title">Trash</span>
    </a>
</li>

{{-- 3. Reports (structure unchanged) --}}
<li class="nav-item nav-category">Reports</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('provincial.report.pending', 'provincial.report.approved') ? 'active' : '' }}" data-bs-toggle="collapse" href="#quarterlyReports" role="button" aria-expanded="false" aria-controls="quarterlyReports">
        <i class="link-icon" data-feather="file-text"></i>
        <span class="link-title">Reports</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
    </a>
    <div class="collapse" id="quarterlyReports">
        <ul class="nav sub-menu">
            <li class="nav-item">
                <a href="{{ route('provincial.report.pending') }}" class="nav-link {{ request()->routeIs('provincial.report.pending') ? 'active' : '' }}">Pending Reports</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('provincial.report.approved') }}" class="nav-link {{ request()->routeIs('provincial.report.approved') ? 'active' : '' }}">Approved Reports</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('aggregated.quarterly.index') ? 'active' : '' }}" data-bs-toggle="collapse" href="#quarterlyReportsView" role="button" aria-expanded="false" aria-controls="quarterlyReportsView">
        <i class="link-icon" data-feather="file-text"></i>
        <span class="link-title">Quarterly Reports</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
    </a>
    <div class="collapse" id="quarterlyReportsView">
        <ul class="nav sub-menu">
            <li class="nav-item">
                <a href="{{ route('aggregated.quarterly.index') }}" class="nav-link {{ request()->routeIs('aggregated.quarterly.index') ? 'active' : '' }}">View Quarterly Reports</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('aggregated.half-yearly.index') ? 'active' : '' }}" data-bs-toggle="collapse" href="#biannualReports" role="button" aria-expanded="false" aria-controls="biannualReports">
        <i class="link-icon" data-feather="file"></i>
        <span class="link-title">Biannual Reports</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
    </a>
    <div class="collapse" id="biannualReports">
        <ul class="nav sub-menu">
            <li class="nav-item">
                <a href="{{ route('aggregated.half-yearly.index') }}" class="nav-link {{ request()->routeIs('aggregated.half-yearly.index') ? 'active' : '' }}">View Half-Yearly Reports</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('aggregated.annual.index') ? 'active' : '' }}" data-bs-toggle="collapse" href="#annualReports" role="button" aria-expanded="false" aria-controls="annualReports">
        <i class="link-icon" data-feather="inbox"></i>
        <span class="link-title">Annual Reports</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
    </a>
    <div class="collapse" id="annualReports">
        <ul class="nav sub-menu">
            <li class="nav-item">
                <a href="{{ route('aggregated.annual.index') }}" class="nav-link {{ request()->routeIs('aggregated.annual.index') ? 'active' : '' }}">View Annual Reports</a>
            </li>
        </ul>
    </div>
</li>

{{-- 4. Team Management (Member, Center, Society only; no Provincial Management) --}}
<li class="nav-item nav-category">Team Management</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('provincial.createExecutor', 'provincial.executors') ? 'active' : '' }}" data-bs-toggle="collapse" href="#manageTeam" role="button" aria-expanded="false" aria-controls="manageTeam">
        <i class="link-icon" data-feather="users"></i>
        <span class="link-title">Manage Team</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
    </a>
    <div class="collapse" id="manageTeam">
        <ul class="nav sub-menu">
            <li class="nav-item">
                <a href="{{ route('provincial.createExecutor') }}" class="nav-link {{ request()->routeIs('provincial.createExecutor') ? 'active' : '' }}">Add Member</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('provincial.executors') }}" class="nav-link {{ request()->routeIs('provincial.executors') ? 'active' : '' }}">View Members</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('provincial.centers', 'provincial.createCenter') ? 'active' : '' }}" data-bs-toggle="collapse" href="#centerManagement" role="button" aria-expanded="false" aria-controls="centerManagement">
        <i class="link-icon" data-feather="map-pin"></i>
        <span class="link-title">Center Management</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
    </a>
    <div class="collapse" id="centerManagement">
        <ul class="nav sub-menu">
            <li class="nav-item">
                <a href="{{ route('provincial.centers') }}" class="nav-link {{ request()->routeIs('provincial.centers') ? 'active' : '' }}">View Centers</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('provincial.createCenter') }}" class="nav-link {{ request()->routeIs('provincial.createCenter') ? 'active' : '' }}">Create Center</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('provincial.societies', 'provincial.createSociety') ? 'active' : '' }}" data-bs-toggle="collapse" href="#societyManagement" role="button" aria-expanded="false" aria-controls="societyManagement">
        <i class="link-icon" data-feather="users"></i>
        <span class="link-title">Society Management</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
    </a>
    <div class="collapse" id="societyManagement">
        <ul class="nav sub-menu">
            <li class="nav-item">
                <a href="{{ route('provincial.societies') }}" class="nav-link {{ request()->routeIs('provincial.societies') ? 'active' : '' }}">View Societies</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('provincial.createSociety') }}" class="nav-link {{ request()->routeIs('provincial.createSociety') ? 'active' : '' }}">Create Society</a>
            </li>
        </ul>
    </div>
</li>

{{-- 5. Notifications (explicit route: notifications.*) --}}
<li class="nav-item nav-category">Notifications</li>
<li class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
    <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
        <i class="link-icon" data-feather="bell"></i>
        <span class="link-title">Notifications</span>
    </a>
</li>

{{-- 6. Settings (Profile, Change Password; explicit routes profile.*) --}}
<li class="nav-item nav-category">Settings</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('profile.edit', 'profile.change-password') ? 'active' : '' }}" data-bs-toggle="collapse" href="#settingsProvincial" role="button" aria-expanded="false" aria-controls="settingsProvincial">
        <i class="link-icon" data-feather="settings"></i>
        <span class="link-title">Settings</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
    </a>
    <div class="collapse" id="settingsProvincial">
        <ul class="nav sub-menu">
            <li class="nav-item">
                <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">Profile</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('profile.change-password') }}" class="nav-link {{ request()->routeIs('profile.change-password') ? 'active' : '' }}">Change Password</a>
            </li>
        </ul>
    </div>
</li>

{{-- 7. User Manual (link only; implementation deferred) --}}
<li class="nav-item nav-category">User Manual</li>
<li class="nav-item {{ request()->routeIs('provincial.user-manual') ? 'active' : '' }}">
    <a href="{{ route('provincial.user-manual') }}" class="nav-link {{ request()->routeIs('provincial.user-manual') ? 'active' : '' }}">
        <i class="link-icon" data-feather="book"></i>
        <span class="link-title">User Manual</span>
    </a>
</li>
