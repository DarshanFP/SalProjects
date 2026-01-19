<!-- resources/views/general/sidebar.blade.php -->
<nav class="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            SAL <span>Projects</span>
        </a>
        <div class="sidebar-toggler not-active">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    <div class="sidebar-body">
        <ul class="nav">
            <!-- Main Section -->
            <li class="nav-item nav-category">Main</li>
            <li class="nav-item">
                <a href="{{ route('general.dashboard') }}" class="nav-link {{ request()->routeIs('general.dashboard') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="box"></i>
                    <span class="link-title">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('activities.all-activities') }}" class="nav-link {{ request()->routeIs('activities.all-activities') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="activity"></i>
                    <span class="link-title">All Activities</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="bell"></i>
                    <span class="link-title">Notifications</span>
                </a>
            </li>

            <!-- My Team Section -->
            <li class="nav-item nav-category">My Team</li>

            <!-- Coordinators Sub-Section -->
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#manageCoordinators" role="button" aria-expanded="false" aria-controls="manageCoordinators">
                    <i class="link-icon" data-feather="users"></i>
                    <span class="link-title">Coordinators</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="manageCoordinators">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('general.createCoordinator') }}" class="nav-link {{ request()->routeIs('general.createCoordinator') ? 'active' : '' }}">Add Coordinator</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('general.coordinators') }}" class="nav-link {{ request()->routeIs('general.coordinators') ? 'active' : '' }}">View Coordinators</a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Provincial Users Sub-Section -->
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#manageProvincials" role="button" aria-expanded="false" aria-controls="manageProvincials">
                    <i class="link-icon" data-feather="user-check"></i>
                    <span class="link-title">Provincial Users</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="manageProvincials">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('general.createProvincial') }}" class="nav-link {{ request()->routeIs('general.createProvincial') ? 'active' : '' }}">Add Provincial</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('general.provincials') }}" class="nav-link {{ request()->routeIs('general.provincials') ? 'active' : '' }}">View Provincials</a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Direct Team Sub-Section (When General acts as Provincial) -->
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#manageDirectTeam" role="button" aria-expanded="false" aria-controls="manageDirectTeam">
                    <i class="link-icon" data-feather="user"></i>
                    <span class="link-title">Direct Team (Executors/Applicants)</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="manageDirectTeam">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('general.createExecutor') }}" class="nav-link {{ request()->routeIs('general.createExecutor') ? 'active' : '' }}">Add Member</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('general.executors') }}" class="nav-link {{ request()->routeIs('general.executors') ? 'active' : '' }}">View Members</a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Province Management Sub-Section -->
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#manageProvinces" role="button" aria-expanded="false" aria-controls="manageProvinces">
                    <i class="link-icon" data-feather="map-pin"></i>
                    <span class="link-title">Province Management</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="manageProvinces">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('general.provinces') }}" class="nav-link {{ request()->routeIs('general.provinces') ? 'active' : '' }}">View Provinces</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('general.createProvince') }}" class="nav-link {{ request()->routeIs('general.createProvince') ? 'active' : '' }}">Create Province</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#manageSocieties" role="button" aria-expanded="false" aria-controls="manageSocieties">
                    <i class="link-icon" data-feather="users"></i>
                    <span class="link-title">Society Management</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="manageSocieties">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('general.societies') }}" class="nav-link {{ request()->routeIs('general.societies') ? 'active' : '' }}">View Societies</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('general.createSociety') }}" class="nav-link {{ request()->routeIs('general.createSociety') ? 'active' : '' }}">Create Society</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#manageCenters" role="button" aria-expanded="false" aria-controls="manageCenters">
                    <i class="link-icon" data-feather="home"></i>
                    <span class="link-title">Center Management</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="manageCenters">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('general.centers') }}" class="nav-link {{ request()->routeIs('general.centers') ? 'active' : '' }}">View Centers</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('general.createCenter') }}" class="nav-link {{ request()->routeIs('general.createCenter') ? 'active' : '' }}">Create Center</a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Projects Section (Combined: Coordinators + Direct Team) -->
            <li class="nav-item nav-category">Projects</li>
            <li class="nav-item">
                <a href="{{ route('general.projects') }}" class="nav-link {{ request()->routeIs('general.projects') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="folder"></i>
                    <span class="link-title">All Projects (Combined)</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('general.projects', ['status' => 'forwarded_to_coordinator']) }}" class="nav-link">
                    <i class="link-icon" data-feather="clock"></i>
                    <span class="link-title">Pending Projects</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('general.projects', ['status' => 'approved_by_coordinator']) }}" class="nav-link">
                    <i class="link-icon" data-feather="check-circle"></i>
                    <span class="link-title">Approved Projects</span>
                </a>
            </li>

            <!-- Reports Section (Combined: Coordinators + Direct Team) -->
            <li class="nav-item nav-category">Reports</li>
            <li class="nav-item">
                <a href="{{ route('general.reports') }}" class="nav-link {{ request()->routeIs('general.reports') && !request()->routeIs('general.reports.pending') && !request()->routeIs('general.reports.approved') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="file-text"></i>
                    <span class="link-title">All Reports (Combined)</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('general.reports.pending') }}" class="nav-link {{ request()->routeIs('general.reports.pending') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="clock"></i>
                    <span class="link-title">Pending Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('general.reports.approved') }}" class="nav-link {{ request()->routeIs('general.reports.approved') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="check-circle"></i>
                    <span class="link-title">Approved Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#monthlyReports" role="button" aria-expanded="false" aria-controls="monthlyReports">
                    <i class="link-icon" data-feather="calendar"></i>
                    <span class="link-title">Monthly Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="monthlyReports">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('general.reports.pending') }}" class="nav-link {{ request()->routeIs('general.reports.pending') ? 'active' : '' }}">Pending Reports</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('general.reports.approved') }}" class="nav-link {{ request()->routeIs('general.reports.approved') ? 'active' : '' }}">Approved Reports</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#quarterlyReports" role="button" aria-expanded="false" aria-controls="quarterlyReports">
                    <i class="link-icon" data-feather="file-text"></i>
                    <span class="link-title">Quarterly Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="quarterlyReports">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('aggregated.quarterly.index') }}" class="nav-link">View Quarterly Reports</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#biannualReports" role="button" aria-expanded="false" aria-controls="biannualReports">
                    <i class="link-icon" data-feather="anchor"></i>
                    <span class="link-title">Biannual Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="biannualReports">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('aggregated.half-yearly.index') }}" class="nav-link">View Half-Yearly Reports</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#annualReports" role="button" aria-expanded="false" aria-controls="annualReports">
                    <i class="link-icon" data-feather="inbox"></i>
                    <span class="link-title">Annual Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="annualReports">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('aggregated.annual.index') }}" class="nav-link">View Annual Reports</a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Budget & Finance Section -->
            <li class="nav-item nav-category">Budget & Finance</li>
            <li class="nav-item">
                <a href="{{ route('general.dashboard') }}" class="nav-link {{ request()->routeIs('general.dashboard') && request('budget_context') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="dollar-sign"></i>
                    <span class="link-title">Budget Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('general.budgets') }}" class="nav-link {{ request()->routeIs('general.budgets') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="briefcase"></i>
                    <span class="link-title">Project Budgets</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('budgets.report') }}" class="nav-link {{ request()->routeIs('budgets.report') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="file-text"></i>
                    <span class="link-title">Budget Reports</span>
                </a>
            </li>

            <!-- Documentation Section -->
            <li class="nav-item nav-category">Documentation</li>
            <li class="nav-item">
                <a href="#" target="_blank" class="nav-link">
                    <i class="link-icon" data-feather="hash"></i>
                    <span class="link-title">Documentation</span>
                </a>
            </li>

            <!-- Settings Section -->
            <li class="nav-item nav-category">Settings</li>
            <li class="nav-item">
                <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="user"></i>
                    <span class="link-title">Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('profile.change-password') }}" class="nav-link {{ request()->routeIs('profile.change-password') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="lock"></i>
                    <span class="link-title">Change Password</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
