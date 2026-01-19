<!-- resources/views/coordinator/sidebar.blade.php -->
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
                <a href="{{ route('admin.dashboard') }}" class="nav-link">
                    <i class="link-icon" data-feather="box"></i>
                    <span class="link-title">Dashboard - Coordinator</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('activities.all-activities') }}" class="nav-link">
                    <i class="link-icon" data-feather="activity"></i>
                    <span class="link-title">All Activities</span>
                </a>
            </li>

            <!-- Web Apps Section -->
            <li class="nav-item nav-category">web apps</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#emails" role="button" aria-expanded="false" aria-controls="emails">
                    <i class="link-icon" data-feather="mail"></i>
                    <span class="link-title">Email</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="emails">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link">Inbox</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Read</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Compose</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="link-icon" data-feather="calendar"></i>
                    <span class="link-title">Calendar</span>
                </a>
            </li>

            <!-- My Team Section -->
            <li class="nav-item nav-category">My Team</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#manageTeam" role="button" aria-expanded="false" aria-controls="manageTeam">
                    <i class="link-icon" data-feather="feather"></i>
                    <span class="link-title">Manage Team</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="manageTeam">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('coordinator.createProvincial') }}" class="nav-link">Add Member</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('coordinator.provincials') }}" class="nav-link">View Member</a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Reports Section -->
            <li class="nav-item nav-category">Reports</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#reportsMenu" role="button" aria-expanded="false" aria-controls="reportsMenu">
                    <i class="link-icon" data-feather="feather"></i>
                    <span class="link-title">Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="reportsMenu">
                    <ul class="nav sub-menu">
                        {{-- <li class="nav-item">
                            <a href="{{ route('coordinator.report.list') }}" class="nav-link">All Project Reports</a>
                        </li> --}}
                        <li class="nav-item">
                            <a href="{{ route('coordinator.report.pending') }}" class="nav-link">Pending Reports</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('coordinator.report.approved') }}" class="nav-link">Approved Reports</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#quarterlyReportsView" role="button" aria-expanded="false" aria-controls="quarterlyReportsView">
                    <i class="link-icon" data-feather="file-text"></i>
                    <span class="link-title">Quarterly Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="quarterlyReportsView">
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

            <!-- Project Application Section -->
            <li class="nav-item nav-category">Project Application</li>
            <li class="nav-item">
                <a href="{{ route('coordinator.projects.list') }}" class="nav-link">
                    <i class="link-icon" data-feather="folder"></i>
                    <span class="link-title">Pending Projects</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('coordinator.approved.projects') }}" class="nav-link">
                    <i class="link-icon" data-feather="check-circle"></i>
                    <span class="link-title">Approved Projects</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#groupProjects" role="button" aria-expanded="false" aria-controls="groupProjects">
                    <i class="link-icon" data-feather="users"></i>
                    <span class="link-title">Group</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="groupProjects">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link">Health</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Education</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Social</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#otherSection" role="button" aria-expanded="false" aria-controls="otherSection">
                    <i class="link-icon" data-feather="cloud-off"></i>
                    <span class="link-title">Other</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="otherSection">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link">404</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">500</a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Docs Section -->
            <li class="nav-item nav-category">Docs</li>
            <li class="nav-item">
                <a href="#" target="_blank" class="nav-link">
                    <i class="link-icon" data-feather="hash"></i>
                    <span class="link-title">Documentation</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
