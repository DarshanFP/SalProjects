<nav class="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">SAL <span>Projects</span></a>
        <div class="sidebar-toggler not-active">
            <span></span><span></span><span></span>
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
                <a href="{{ route('admin.activities.all') }}" class="nav-link">
                    <i class="link-icon" data-feather="activity"></i>
                    <span class="link-title">All Activities</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.projects.index') }}" class="nav-link">
                    <i class="link-icon" data-feather="folder"></i>
                    <span class="link-title">Projects</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.reports.index') }}" class="nav-link">
                    <i class="link-icon" data-feather="file-text"></i>
                    <span class="link-title">Reports</span>
                </a>
            </li>

            @if(config('budget.admin_reconciliation_enabled', false))
            <li class="nav-item nav-category">Governance</li>
            <li class="nav-item">
                <a href="{{ route('admin.budget-reconciliation.index') }}" class="nav-link">
                    <i class="link-icon" data-feather="dollar-sign"></i>
                    <span class="link-title">Budget Reconciliation</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.budget-reconciliation.log') }}" class="nav-link">
                    <i class="link-icon" data-feather="list"></i>
                    <span class="link-title">Correction Log</span>
                </a>
            </li>
            @endif

            @if(config('admin.impersonation_enabled', false))
            <li class="nav-item nav-category">Impersonation</li>
            <li class="nav-item">
                <span class="nav-link text-muted" title="Coming later — no backend yet">
                    <i class="link-icon" data-feather="user"></i>
                    <span class="link-title">Act as Executor</span>
                    <small class="d-block text-muted">Coming later</small>
                </span>
            </li>
            <li class="nav-item">
                <span class="nav-link text-muted" title="Coming later — no backend yet">
                    <i class="link-icon" data-feather="user"></i>
                    <span class="link-title">Act as Coordinator</span>
                    <small class="d-block text-muted">Coming later</small>
                </span>
            </li>
            <li class="nav-item">
                <span class="nav-link text-muted" title="Coming later — no backend yet">
                    <i class="link-icon" data-feather="user"></i>
                    <span class="link-title">Act as Provincial</span>
                    <small class="d-block text-muted">Coming later</small>
                </span>
            </li>
            <li class="nav-item">
                <span class="nav-link text-muted" title="Exit impersonation — no backend yet">
                    <i class="link-icon" data-feather="log-out"></i>
                    <span class="link-title">Exit Impersonation</span>
                    <small class="d-block text-muted">Coming later</small>
                </span>
            </li>
            @endif
        </ul>
    </div>
</nav>
