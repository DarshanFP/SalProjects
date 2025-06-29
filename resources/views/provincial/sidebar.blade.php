<!-- resources/views/provincial/sidebar.blade.php -->
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
                <a href="{{ route('provincial.dashboard') }}" class="nav-link">
                    <i class="link-icon" data-feather="box"></i>
                    <span class="link-title">Dashboard - Provincial</span>
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
                    <i class="link-icon" data-feather="users"></i>
                    <span class="link-title">Manage Team</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="manageTeam">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('provincial.createExecutor') }}" class="nav-link">Add my Member</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('provincial.executors') }}" class="nav-link">View my Member</a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Reports Section -->
            <li class="nav-item nav-category">Reports</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#quarterlyReports" role="button" aria-expanded="false" aria-controls="quarterlyReports">
                    <i class="link-icon" data-feather="file-text"></i>
                    <span class="link-title">Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="quarterlyReports">
                    <ul class="nav sub-menu">
                        {{-- <li class="nav-item">
                            <a href="{{ route('provincial.report.list') }}" class="nav-link">All Project Reports</a>
                        </li> --}}
                        <li class="nav-item">
                            <a href="{{ route('provincial.report.pending') }}" class="nav-link">Pending Reports</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('provincial.report.approved') }}" class="nav-link">Approved Reports</a>
                        </li>
                        {{-- Uncomment or add more reports as needed
                        <li class="nav-item">
                            <a href="#" class="nav-link">Development Livelihood</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Institutional / Non-Inst Support</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Women in Distress</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Skill Training</a>
                        </li>
                        --}}
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#biannualReports" role="button" aria-expanded="false" aria-controls="biannualReports">
                    <i class="link-icon" data-feather="file"></i>
                    <span class="link-title">Biannual Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <!-- Uncomment and add routes if needed
                <div class="collapse" id="biannualReports">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link">Report 1</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Report 2</a>
                        </li>
                    </ul>
                </div>
                -->
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#annualReports" role="button" aria-expanded="false" aria-controls="annualReports">
                    <i class="link-icon" data-feather="inbox"></i>
                    <span class="link-title">Annual Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <!-- Uncomment and add routes if needed
                <div class="collapse" id="annualReports">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link">Report 1</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Report 2</a>
                        </li>
                    </ul>
                </div>
                -->
            </li>

            <!-- Project Application Section -->
            <li class="nav-item nav-category">Project Application</li>
            {{-- <li class="nav-item">
                <!-- Existing All Projects List for everyone -->
                <a href="{{ route('projects.list') }}" class="nav-link">
                    <i class="link-icon" data-feather="folder"></i>
                    <span class="link-title">All Projects List</span>
                </a>
            </li> --}}
            <li class="nav-item nav-category">Projects</li>
            <li class="nav-item">
                <a href="{{ route('provincial.projects.list') }}" class="nav-link">
                    <i class="link-icon" data-feather="folder"></i>
                    <span class="link-title">Pending Projects</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('provincial.approved.projects') }}" class="nav-link">
                    <i class="link-icon" data-feather="check-circle"></i>
                    <span class="link-title">Approved Projects</span>
                </a>
            </li>

            {{-- If needed, you can have collapsible menus for other project categories --}}
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
