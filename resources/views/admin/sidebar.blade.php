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
        <li class="nav-item nav-category">Main</li>
        <li class="nav-item">
            <a href="{{ route('admin.dashboard') }}" class="nav-link">
            <i class="link-icon" data-feather="box"></i>
            <span class="link-title">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('activities.all-activities') }}" class="nav-link">
            <i class="link-icon" data-feather="activity"></i>
            <span class="link-title">All Activities</span>
            </a>
        </li>
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
                <a href="pages/email/inbox.html" class="nav-link">Inbox</a>
                </li>
                <li class="nav-item">
                <a href="pages/email/read.html" class="nav-link">Read</a>
                </li>
                <li class="nav-item">
                <a href="pages/email/compose.html" class="nav-link">Compose</a>
                </li>
            </ul>
            </div>
        </li>
        <li class="nav-item">
            <a href="pages/apps/calendar.html" class="nav-link">
            <i class="link-icon" data-feather="calendar"></i>
            <span class="link-title">Calendar</span>
            </a>
        </li>
        <li class="nav-item nav-category">Reports</li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#uiComponents" role="button" aria-expanded="false" aria-controls="uiComponents">
            <i class="link-icon" data-feather="feather"></i>
            <span class="link-title">Quarterly Reports</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="uiComponents">
                <ul class="nav sub-menu">
                    <li class="nav-item">
                        <a href="{{ route('aggregated.quarterly.index') }}" class="nav-link">View Quarterly Reports</a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#advancedUI" role="button" aria-expanded="false" aria-controls="advancedUI">
            <i class="link-icon" data-feather="anchor"></i>
            <span class="link-title">Biannual Reports</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="advancedUI">
                <ul class="nav sub-menu">
                    <li class="nav-item">
                        <a href="{{ route('aggregated.half-yearly.index') }}" class="nav-link">View Half-Yearly Reports</a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#forms" role="button" aria-expanded="false" aria-controls="forms">
            <i class="link-icon" data-feather="inbox"></i>
            <span class="link-title">Annual Reports</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="forms">
                <ul class="nav sub-menu">
                    <li class="nav-item">
                        <a href="{{ route('aggregated.annual.index') }}" class="nav-link">View Annual Reports</a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item nav-category">Project Application</li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#general-pages" role="button" aria-expanded="false" aria-controls="general-pages">
            <i class="link-icon" data-feather="book"></i>
            <span class="link-title">Individual</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="general-pages">
            <ul class="nav sub-menu">
                <li class="nav-item">
                <a href="pages/general/blank-page.html" class="nav-link">Health</a>
                </li>
                <li class="nav-item">
                <a href="pages/general/faq.html" class="nav-link">Education</a>
                </li>
                <li class="nav-item">
                <a href="pages/general/invoice.html" class="nav-link">Social</a>
                </li>
            </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#authPages" role="button" aria-expanded="false" aria-controls="authPages">
            <i class="link-icon" data-feather="unlock"></i>
            <span class="link-title">Group</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="authPages">
            <ul class="nav sub-menu">
                <li class="nav-item">
                <a href="pages/auth/login.html" class="nav-link">Health</a>
                </li>
                <li class="nav-item">
                <a href="pages/auth/register.html" class="nav-link">Education</a>
                </li>
                <li class="nav-item">
                <a href="pages/auth/register.html" class="nav-link">Social</a>
                </li>
            </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#errorPages" role="button" aria-expanded="false" aria-controls="errorPages">
            <i class="link-icon" data-feather="cloud-off"></i>
            <span class="link-title">Other</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="errorPages">
            <ul class="nav sub-menu">
                <li class="nav-item">
                <a href="pages/error/404.html" class="nav-link">404</a>
                </li>
                <li class="nav-item">
                <a href="pages/error/500.html" class="nav-link">500</a>
                </li>
            </ul>
            </div>
        </li>
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
