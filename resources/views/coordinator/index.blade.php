@extends('coordinator.dashboard')

@section('content')
<div class="page-content coordinator-dashboard">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i data-feather="layout" class="me-2"></i>Coordinator Dashboard
                    </h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="refreshDashboard()" title="Refresh Dashboard Data">
                            <i data-feather="refresh-cw"></i> Refresh
                        </button>
                        <a href="{{ route('projects.create') }}" class="btn btn-sm btn-primary">
                            <i data-feather="plus"></i> Create Project
                        </a>
                        <a href="{{ route('coordinator.report.list') }}" class="btn btn-sm btn-success">
                            <i data-feather="file-text"></i> View Reports
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Success/Error Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {!! session('success') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Project Statistics Cards with Fixed Height -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary" style="height: 120px;">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Total Projects</h6>
                                        <h3 class="card-text mb-0">{{ format_indian_integer($statistics['total_projects']) }}</h3>
                                    </div>
                                    <i data-feather="folder" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success" style="height: 120px;">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Approved Projects</h6>
                                        <h3 class="card-text mb-0">{{ format_indian_integer($statistics['projects_by_status']['approved_by_coordinator'] ?? 0) }}</h3>
                                    </div>
                                    <i data-feather="check-circle" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning" style="height: 120px;">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Pending Review</h6>
                                        <h3 class="card-text mb-0">{{ format_indian_integer(($statistics['projects_by_status']['forwarded_to_coordinator'] ?? 0) + ($statistics['projects_by_status']['submitted_to_provincial'] ?? 0)) }}</h3>
                                    </div>
                                    <i data-feather="clock" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info" style="height: 120px;">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Project Types</h6>
                                        <h3 class="card-text mb-0">{{ format_indian_integer(count($statistics['projects_by_type'])) }}</h3>
                                    </div>
                                    <i data-feather="layers" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECTION 1: Budget Overview (Like Provincial Dashboard - Budget Summary & Details with Filters) --}}
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="text-muted mb-3">
                                <i data-feather="dollar-sign" style="width: 18px; height: 18px;" class="me-2"></i>
                                Budget Overview
                            </h5>
                        </div>
                    </div>
                    <div class="row mb-4">
                        {{-- System Budget Overview Widget (shows approved/in pipeline expenses with filters) --}}
                        <div class="col-md-12 mb-4">
                            @include('coordinator.widgets.system-budget-overview')
                        </div>
                    </div>

                    {{-- SECTION 2: Action Widgets (User can take actions here) --}}
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="text-muted mb-3">
                                <i data-feather="check-circle" style="width: 18px; height: 18px;" class="me-2"></i>
                                Actions Required
                            </h5>
                        </div>
                    </div>
                    <div class="row mb-4">
                        {{-- Pending Approvals Widget (Full Width) --}}
                        <div class="col-md-12 mb-4">
                            @include('coordinator.widgets.pending-approvals')
                        </div>
                    </div>

                    {{-- SECTION 3: Project & Report Information Widgets --}}
                    <div class="row mb-4">
                        {{-- Provincial Overview Widget --}}
                        <div class="col-md-12 mb-4">
                            @include('coordinator.widgets.provincial-overview')
                        </div>
                    </div>

                    {{-- Budget Charts Section (extracted from Budget Overview widget) --}}
                    <div class="row mb-4">
                        <div class="col-md-12 mb-4">
                            @include('coordinator.widgets.budget-charts')
                        </div>
                    </div>

                    <div class="row mb-4">
                        {{-- System Activity Feed Widget --}}
                        <div class="col-md-12 mb-4">
                            @include('coordinator.widgets.system-activity-feed')
                        </div>
                    </div>

                    <div class="row mb-4">
                        {{-- Provincial Management Widget --}}
                        <div class="col-md-12 mb-4">
                            @include('coordinator.widgets.provincial-management')
                        </div>
                    </div>

                    <div class="row mb-4">
                        {{-- System Performance Summary Widget --}}
                        <div class="col-md-12 mb-4">
                            @include('coordinator.widgets.system-performance')
                        </div>
                    </div>

                    {{-- SECTION 4: Charts & Analytics (All visualization widgets at the end) --}}
                    <div class="row mb-4">
                        {{-- System Analytics Charts Widget (Full Width) --}}
                        <div class="col-md-12 mb-4">
                            @include('coordinator.widgets.system-analytics')
                        </div>
                    </div>

                    <div class="row mb-4">
                        {{-- Province Performance Comparison Widget --}}
                        <div class="col-md-6 mb-4">
                            @include('coordinator.widgets.province-comparison')
                        </div>
                        {{-- System Health Indicators Widget --}}
                        <div class="col-md-6 mb-4">
                            @include('coordinator.widgets.system-health')
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Projects by Status</h5>
                                </div>
                                <div class="card-body">
                                    <div id="statusDistributionChart" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Projects by Type</h5>
                                </div>
                                <div class="card-body">
                                    <div id="projectTypeChart" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions and Recent Activity -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('projects.create') }}" class="btn btn-primary">
                                            <i class="feather icon-plus"></i> Create New Project
                                        </a>
                                        <a href="{{ route('coordinator.report.list') }}" class="btn btn-success">
                                            <i class="feather icon-file-text"></i> View Monthly Reports
                                        </a>
                                        <a href="{{ route('coordinator.budget-overview') }}" class="btn btn-info">
                                            <i class="feather icon-bar-chart-2"></i> Budget Overview
                                        </a>
                                        <a href="{{ route('coordinator.projects.list') }}" class="btn btn-secondary">
                                            <i class="feather icon-list"></i> View All Projects
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Projects</h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        @forelse($statistics['recent_projects'] as $project)
                                            <a href="{{ route('coordinator.projects.show', $project->project_id) }}" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">{{ $project->project_title ?? $project->project_id }}</h6>
                                                    <small>{{ $project->created_at->diffForHumans() }}</small>
                                                </div>
                                                <p class="mb-1">
                                                    <span class="badge badge-{{ $project->status === 'approved_by_coordinator' ? 'success' : 'warning' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                                    </span>
                                                    <span class="badge badge-info">{{ $project->project_type }}</span>
                                                </p>
                                            </a>
                                        @empty
                                            <div class="list-group-item text-muted">No recent projects</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('coordinator.dashboard') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="center" class="form-label">Center</label>
                                <select name="center" id="center" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Centers</option>
                                    @foreach($centers as $center)
                                        <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                            {{ $center }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="role" class="form-label">Role</label>
                                <select name="role" id="role" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Roles</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                            {{ ucfirst($role) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="parent_id" class="form-label">Parent (Provincial)</label>
                                <select name="parent_id" id="parent_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Parents</option>
                                    @foreach($parents as $parent)
                                        <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }} ({{ $parent->province }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="{{ route('coordinator.dashboard') }}" class="btn btn-secondary">Clear Filters</a>
                            </div>
                        </div>
                    </form>

                    <!-- Active Filters Display -->
                    @if(request('province') || request('center') || request('role') || request('parent_id'))
                    <div class="alert alert-info mb-4">
                        <strong>Active Filters:</strong>
                        @if(request('province'))
                            <span class="badge badge-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('center'))
                            <span class="badge badge-success me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('role'))
                            <span class="badge badge-warning me-2">Role: {{ ucfirst(request('role')) }}</span>
                        @endif
                        @if(request('parent_id'))
                            @php
                                $selectedParent = $parents->firstWhere('id', request('parent_id'));
                            @endphp
                            @if($selectedParent)
                                <span class="badge badge-info me-2">Parent: {{ $selectedParent->name }}</span>
                            @endif
                        @endif
                        <a href="{{ route('coordinator.dashboard') }}" class="btn btn-sm btn-outline-secondary float-right">Clear All</a>
                    </div>
                    @endif

                    <!-- Debug Information -->
                    {{-- @if(config('app.debug'))
                    <div class="alert alert-info">
                        <strong>Debug Info:</strong><br>
                        Selected Province: {{ request('province') ?: 'None' }}<br>
                        Selected Center: {{ request('center') ?: 'None' }}<br>
                        Selected Role: {{ request('role') ?: 'None' }}<br>
                        Selected Parent: {{ request('parent_id') ?: 'None' }}<br>
                        Available Provinces: {{ $provinces->count() }}<br>
                        Available Centers: {{ $centers->count() }}<br>
                        Available Parents: {{ $parents->count() }}
                    </div>
                    @endif --}}

                    <!-- Budget Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="text-white card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Total Budget</h5>
                                    <h3 class="card-text">{{ format_indian_currency($budgetSummaries['total']['total_budget']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-white card bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Total Expenses</h5>
                                    <h3 class="card-text">{{ format_indian_currency($budgetSummaries['total']['total_expenses']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-white card bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Remaining Budget</h5>
                                    <h3 class="card-text">{{ format_indian_currency($budgetSummaries['total']['total_remaining']) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Budget by Project Type -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Budget by Project Type</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Project Type</th>
                                                    <th>Budget</th>
                                                    <th>Expenses</th>
                                                    <th>Remaining</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($budgetSummaries['by_project_type'] as $type => $summary)
                                                <tr>
                                                    <td>{{ $type }}</td>
                                                    <td>{{ format_indian_currency($summary['total_budget']) }}</td>
                                                    <td>{{ format_indian_currency($summary['total_expenses']) }}</td>
                                                    <td>{{ format_indian_currency($summary['total_remaining']) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Budget by Province -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Budget by Province</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Province</th>
                                                    <th>Budget</th>
                                                    <th>Expenses</th>
                                                    <th>Remaining</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($budgetSummaries['by_province'] as $province => $summary)
                                                <tr>
                                                    <td>{{ $province }}</td>
                                                    <td>{{ format_indian_currency($summary['total_budget']) }}</td>
                                                    <td>{{ format_indian_currency($summary['total_expenses']) }}</td>
                                                    <td>{{ format_indian_currency($summary['total_remaining']) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if ApexCharts is available
    if (typeof ApexCharts !== 'undefined') {
        initializeDashboardCharts();
    }

    // Debug form submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const formData = new FormData(form);
            // console.log('Form submitting with data:');
            for (let [key, value] of formData.entries()) {
                // console.log(key + ': ' + value);
            }
        });
    }
});

function initializeDashboardCharts() {
    // Status Distribution Chart
    const statusData = @json($statistics['projects_by_status']);
    const statusChart = new ApexCharts(document.querySelector("#statusDistributionChart"), {
        series: Object.values(statusData),
        chart: {
            type: 'donut',
            height: 300
        },
        labels: Object.keys(statusData).map(status => status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())),
        colors: ['#667eea', '#11998e', '#f59e0b', '#ef4444', '#8b5cf6'],
        legend: {
            position: 'bottom'
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' projects';
                }
            }
        }
    });
    statusChart.render();

    // Project Type Distribution Chart
    const typeData = @json($statistics['projects_by_type']);
    const typeChart = new ApexCharts(document.querySelector("#projectTypeChart"), {
        series: Object.values(typeData),
        chart: {
            type: 'pie',
            height: 300
        },
        labels: Object.keys(typeData),
        colors: ['#667eea', '#11998e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#10b981'],
        legend: {
            position: 'bottom'
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' projects';
                }
            }
        }
    });
    typeChart.render();

    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Refresh Dashboard (clears cache and reloads)
window.refreshDashboard = function() {
    if (confirm('This will refresh all dashboard data. Continue?')) {
        // Show loading state
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Refreshing...';

        // Create a form to submit POST request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("coordinator.dashboard.refresh") }}';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        document.body.appendChild(form);
        form.submit();
    }
};

// Widget toggle (minimize/maximize) functionality
document.addEventListener('click', function(e) {
    if (e.target.closest('.widget-toggle')) {
        const toggle = e.target.closest('.widget-toggle');
        const widgetId = toggle.dataset.widget;
        const widgetCard = document.querySelector(`[data-widget-id="${widgetId}"]`);

        if (widgetCard) {
            const widgetContent = widgetCard.querySelector('.widget-content');
            const icon = toggle.querySelector('i');

            if (widgetContent) {
                if (widgetContent.style.display === 'none') {
                    widgetContent.style.display = '';
                    if (icon) {
                        icon.setAttribute('data-feather', 'chevron-up');
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                    }
                    toggle.title = 'Minimize';
                } else {
                    widgetContent.style.display = 'none';
                    if (icon) {
                        icon.setAttribute('data-feather', 'chevron-down');
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                    }
                    toggle.title = 'Maximize';
                }
            }
        }
    }
});
</script>
@endpush

@push('styles')
<style>
/* Ensure all table rows have clear/transparent backgrounds matching dark theme */
.coordinator-dashboard .table tbody tr,
.table tbody tr {
    background-color: transparent !important;
}

/* Override Bootstrap table striped backgrounds */
.coordinator-dashboard .table-striped tbody tr:nth-of-type(odd),
.table-striped tbody tr:nth-of-type(odd) {
    background-color: transparent !important;
}

.coordinator-dashboard .table-striped tbody tr:nth-of-type(even),
.table-striped tbody tr:nth-of-type(even) {
    background-color: transparent !important;
}

/* Override Bootstrap colored table row classes - remove all colored backgrounds */
.coordinator-dashboard .table-danger,
.coordinator-dashboard .table-warning,
.coordinator-dashboard .table-success,
.coordinator-dashboard .table-info,
.coordinator-dashboard .table-primary,
.table-danger,
.table-warning,
.table-success,
.table-info,
.table-primary {
    background-color: transparent !important;
    color: inherit !important;
}

/* Keep subtle hover effect for dark theme */
.coordinator-dashboard .table-hover tbody tr:hover,
.table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.03) !important;
}

/* Ensure activity feed items have clear backgrounds */
.coordinator-dashboard .activity-item,
.activity-item {
    background-color: transparent !important;
}

.coordinator-dashboard .activity-item:hover,
.activity-item:hover {
    background-color: rgba(255, 255, 255, 0.03) !important;
}
</style>
@endpush

@endsection
