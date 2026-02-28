@extends('executor.dashboard')

@section('content')
@php
    use App\Constants\ProjectStatus;

    // Widget IDs for customization
    $widgetIds = [
        'action-items' => isset($actionItems) && $actionItems['total_pending'] > 0,
        'report-status-summary' => isset($reportStatusSummary),
        'upcoming-deadlines' => isset($upcomingDeadlines) && $upcomingDeadlines['total'] > 0,
        'quick-stats' => isset($quickStats),
            'project-health' => isset($projectHealthSummary) && isset($ownedProjects) && $ownedProjects->total() > 0,
            'activity-feed' => isset($recentActivities) && $recentActivities->count() > 0,
            'project-status-visualization' => isset($ownedProjects) && $ownedProjects->total() > 0,
            'report-analytics' => isset($reportChartData),
            'budget-analytics' => isset($chartData) && !empty($chartData),
            'report-overview' => isset($reportStatusSummary),
            'projects-requiring-attention' => isset($projectsRequiringAttention) && $projectsRequiringAttention['total'] > 0,
            'reports-requiring-attention' => isset($reportsRequiringAttention) && $reportsRequiringAttention['total'] > 0,
            'project-budgets-overview' => isset($budgetSummaries),
        ];
@endphp

<div class="page-content">
    {{-- Dashboard Customization Button --}}
    <div class="mb-3 text-end">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleDashboardCustomization()">
            <i data-feather="settings" style="width: 16px; height: 16px;"></i>
            Customize Dashboard
        </button>
    </div>

    {{-- Dashboard Customization Panel --}}
    <div id="dashboardCustomizationPanel" class="card mb-4" style="display: none;">
        <div class="card-header">
            <h5 class="mb-0">
                <i data-feather="sliders" class="me-2"></i>
                Dashboard Customization
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-3">Show/Hide Widgets</h6>
                    <div class="list-group">
                        @foreach([
                            'action-items' => 'Action Items',
                            'report-status-summary' => 'Report Status Summary',
                            'upcoming-deadlines' => 'Upcoming Deadlines',
                            'quick-stats' => 'Quick Stats',
                            'project-health' => 'Project Health',
                            'activity-feed' => 'Activity Feed',
                            'project-status-visualization' => 'Project Status Charts',
                            'report-analytics' => 'Report Analytics',
                            'budget-analytics' => 'Budget Analytics',
                            'report-overview' => 'Report Overview',
                            'projects-requiring-attention' => 'Projects Requiring Attention',
                            'reports-requiring-attention' => 'Reports Requiring Attention',
                            'project-budgets-overview' => 'Project Budgets Overview',
                        ] as $widgetId => $widgetName)
                            <div class="list-group-item bg-dark border-secondary d-flex justify-content-between align-items-center">
                                <span class="text-white">{{ $widgetName }}</span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input widget-toggle"
                                           type="checkbox"
                                           id="widget-{{ $widgetId }}"
                                           data-widget-id="{{ $widgetId }}"
                                           {{ ($widgetIds[$widgetId] ?? true) ? 'checked' : '' }}
                                           onchange="toggleWidget('{{ $widgetId }}', this.checked)">
                                    <label class="form-check-label text-white" for="widget-{{ $widgetId }}"></label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3">Reorder Widgets</h6>
                    <p class="text-muted small mb-3">
                        <i data-feather="info" style="width: 14px; height: 14px;"></i>
                        Drag widgets in the dashboard to reorder them. Changes are saved automatically.
                    </p>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <small>
                            <i data-feather="info" style="width: 14px; height: 14px;"></i>
                            Widgets are reordered by dragging them directly in the dashboard. Use the "Show/Hide" toggles above to control visibility.
                        </small>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-top border-secondary text-end">
                <button type="button" class="btn btn-secondary" onclick="resetDashboardLayout()">
                    <i data-feather="rotate-ccw" style="width: 14px; height: 14px;"></i>
                    Reset to Default
                </button>
                <button type="button" class="btn btn-primary" onclick="saveDashboardLayout()">
                    <i data-feather="save" style="width: 14px; height: 14px;"></i>
                    Save Layout
                </button>
            </div>
        </div>
    </div>

    {{-- Dashboard Widgets Section --}}
    <div id="dashboardWidgetsContainer" class="row">
        {{-- Project Budgets Overview Widget - Full width, first widget --}}
        <div class="widget-container col-12" data-widget-id="project-budgets-overview" data-widget-default="true" title="Drag to reorder">
            @include('executor.widgets.project-budgets-overview')
        </div>

        {{-- Projects Requiring Attention Widget - Full width --}}
        @if(isset($projectsRequiringAttention) && $projectsRequiringAttention['total'] > 0)
            <div class="widget-container col-12" data-widget-id="projects-requiring-attention" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.projects-requiring-attention')
            </div>
        @endif

        {{-- Action Items Widget - 50% width --}}
        @if(isset($actionItems))
            <div class="widget-container col-12 col-md-6" data-widget-id="action-items" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.action-items')
            </div>
        @endif

        {{-- Reports Requiring Attention Widget - 50% width --}}
        @if(isset($reportsRequiringAttention) && $reportsRequiringAttention['total'] > 0)
            <div class="widget-container col-12 col-md-6" data-widget-id="reports-requiring-attention" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.reports-requiring-attention')
            </div>
        @endif

        {{-- Report Status Summary Widget - 50% width --}}
        @if(isset($reportStatusSummary))
            <div class="widget-container col-12 col-md-6" data-widget-id="report-status-summary" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.report-status-summary')
            </div>
        @endif

        {{-- Upcoming Deadlines Widget - Full width below --}}
        @if(isset($upcomingDeadlines))
            <div class="widget-container col-12" data-widget-id="upcoming-deadlines" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.upcoming-deadlines')
            </div>
        @endif

        {{-- Quick Stats Widget --}}
        @if(isset($quickStats))
            <div class="widget-container col-12" data-widget-id="quick-stats" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.quick-stats')
            </div>
        @endif

        {{-- Project Health Widget --}}
        @if(isset($projectHealthSummary) && isset($ownedProjects) && $ownedProjects->total() > 0)
            <div class="widget-container col-12 col-md-6" data-widget-id="project-health" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.project-health')
            </div>
        @endif

        {{-- Recent Activity Feed Widget --}}
        @if(isset($recentActivities))
            <div class="widget-container col-12 col-md-6" data-widget-id="activity-feed" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.activity-feed')
            </div>
        @endif

        {{-- Project Status Visualization Widget - Full width on larger screens --}}
        @if(isset($ownedProjects) && $ownedProjects->total() > 0)
            <div class="widget-container col-12" data-widget-id="project-status-visualization" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.project-status-visualization')
            </div>
        @endif

        {{-- Report Analytics Widget - Full width on larger screens --}}
        @if(isset($reportChartData))
            <div class="widget-container col-12" data-widget-id="report-analytics" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.report-analytics')
            </div>
        @endif

        {{-- Report Overview Widget - Full width on larger screens --}}
        @if(isset($reportStatusSummary))
            <div class="widget-container col-12 col-lg-6" data-widget-id="report-overview" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.report-overview')
            </div>
        @endif

        {{-- Budget Analytics Widget - Full width on larger screens --}}
        @if(isset($chartData) && !empty($chartData))
            <div class="widget-container col-12 col-lg-6" data-widget-id="budget-analytics" data-widget-default="true" title="Drag to reorder">
                @include('executor.widgets.budget-analytics')
            </div>
        @endif
    </div>

    {{-- Projects List Section --}}
    <div class="row justify-content-center mt-4">
        <div class="col-md-12 col-xl-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i data-feather="folder" class="me-2"></i>
                        My Projects (Owned)
                        <span class="badge bg-primary ms-2">{{ $ownedCount ?? 0 }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Projects List -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex gap-2">
                                {{-- Project Type Tabs/Filter --}}
                                <div class="btn-group" role="group">
                                    <a href="{{ route('executor.dashboard', array_merge(request()->except(['show', 'page', 'owned_page', 'incharge_page']), ['show' => 'approved'])) }}"
                                       class="btn btn-sm {{ (!isset($showType) || $showType === 'approved') ? 'btn-primary' : 'btn-outline-secondary' }}">
                                        <i data-feather="check-circle" style="width: 14px; height: 14px;"></i>
                                        Approved
                                    </a>
                                    <a href="{{ route('executor.dashboard', array_merge(request()->except(['show', 'page', 'owned_page', 'incharge_page']), ['show' => 'needs_work'])) }}"
                                       class="btn btn-sm {{ (isset($showType) && $showType === 'needs_work') ? 'btn-warning' : 'btn-outline-secondary' }}">
                                        <i data-feather="alert-triangle" style="width: 14px; height: 14px;"></i>
                                        Needs Work
                                        @if(isset($projectsRequiringAttention) && $projectsRequiringAttention['total'] > 0)
                                            <span class="badge bg-danger ms-1">{{ $projectsRequiringAttention['total'] }}</span>
                                        @endif
                                    </a>
                                    <a href="{{ route('executor.dashboard', array_merge(request()->except(['show', 'page', 'owned_page', 'incharge_page']), ['show' => 'all'])) }}"
                                       class="btn btn-sm {{ (isset($showType) && $showType === 'all') ? 'btn-info' : 'btn-outline-secondary' }}">
                                        <i data-feather="list" style="width: 14px; height: 14px;"></i>
                                        All
                                    </a>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#projectFilters" aria-expanded="false">
                                    <i data-feather="filter" style="width: 14px; height: 14px;"></i>
                                    Filters
                                </button>
                                <a href="{{ route('executor.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                                    <i data-feather="refresh-cw" style="width: 14px; height: 14px;"></i>
                                    Reset
                                </a>
                            </div>
                        </div>

                        {{-- Search and Filters --}}
                        <div class="collapse mb-3" id="projectFilters">
                            <div class="card card-body bg-dark">
                                <form method="GET" action="{{ route('executor.dashboard') }}" class="row g-3">
                                    {{-- Hidden field to preserve show type --}}
                                    @if(request('show'))
                                        <input type="hidden" name="show" value="{{ request('show') }}">
                                    @endif

                                    {{-- Search (filters apply to both Owned and In-Charge lists) --}}
                                    <div class="col-md-4">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text"
                                               name="search"
                                               id="search"
                                               class="form-control"
                                               placeholder="Project ID, Title, Society, Place..."
                                               value="{{ request('search') }}">
                                    </div>

                                    {{-- Project Type Filter --}}
                                    <div class="col-md-3">
                                        <label for="project_type" class="form-label">Project Type</label>
                                        <select name="project_type" id="project_type" class="form-select">
                                            <option value="">All Types</option>
                                            @foreach($projectTypes as $type)
                                                <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                                    {{ $type }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Sort By --}}
                                    <div class="col-md-2">
                                        <label for="sort_by" class="form-label">Sort By</label>
                                        <select name="sort_by" id="sort_by" class="form-select">
                                            <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Date Created</option>
                                            <option value="project_id" {{ request('sort_by') == 'project_id' ? 'selected' : '' }}>Project ID</option>
                                            <option value="project_title" {{ request('sort_by') == 'project_title' ? 'selected' : '' }}>Title</option>
                                            <option value="project_type" {{ request('sort_by') == 'project_type' ? 'selected' : '' }}>Type</option>
                                        </select>
                                    </div>

                                    {{-- Sort Order --}}
                                    <div class="col-md-2">
                                        <label for="sort_order" class="form-label">Order</label>
                                        <select name="sort_order" id="sort_order" class="form-select">
                                            <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Descending</option>
                                            <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                        </select>
                                    </div>

                                    {{-- Per Page --}}
                                    <div class="col-md-1">
                                        <label for="per_page" class="form-label">Per Page</label>
                                        <select name="per_page" id="per_page" class="form-select">
                                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                                            <option value="15" {{ request('per_page') == '15' ? 'selected' : '' }}>15</option>
                                            <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i data-feather="search" style="width: 14px; height: 14px;"></i>
                                            Apply Filters
                                        </button>
                                        <a href="{{ route('executor.dashboard') }}" class="btn btn-secondary">
                                            <i data-feather="x" style="width: 14px; height: 14px;"></i>
                                            Clear
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Active Filters Display --}}
                        @if(request()->hasAny(['search', 'project_type', 'sort_by']))
                            <div class="mb-3">
                                <small class="text-muted">Active filters: </small>
                                @if(request('search'))
                                    <span class="badge bg-info me-1">Search: {{ request('search') }}</span>
                                @endif
                                @if(request('project_type'))
                                    <span class="badge bg-info me-1">Type: {{ request('project_type') }}</span>
                                @endif
                                @if(request('sort_by'))
                                    <span class="badge bg-info me-1">Sort: {{ request('sort_by') }} ({{ request('sort_order', 'desc') }})</span>
                                @endif
                            </div>
                        @endif

                        {{-- Projects Table --}}
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Project ID</th>
                                        <th style="min-width: 200px;">Project Title</th>
                                        <th>Project Type</th>
                                        <th>Budget</th>
                                        <th>Expenses</th>
                                        <th>Utilization</th>
                                        <th>Health</th>
                                        <th>Last Report</th>
                                        <th>Status</th>
                                        <th style="min-width: 200px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ownedProjects as $project)
                                        @php
                                            $metadata = $enhancedOwnedProjects[$project->project_id] ?? null;
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('projects.show', $project->project_id) }}"
                                                   class="text-primary text-decoration-none fw-bold"
                                                   title="View Project">
                                                    {{ $project->project_id }}
                                                </a>
                                            </td>
                                            <td style="max-width: 300px; word-wrap: break-word; white-space: normal;">
                                                <div class="text-wrap" title="{{ $project->project_title }}">
                                                    {{ $project->project_title }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $project->project_type }}</span>
                                            </td>
                                            <td>
                                                @if($metadata)
                                                    <small class="text-muted">{{ format_indian_currency($metadata['budget'], 2) }}</small>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($metadata)
                                                    <small class="text-muted">{{ format_indian_currency($metadata['expenses'], 2) }}</small>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($metadata)
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress me-2" style="width: 60px; height: 20px;">
                                                            <div class="progress-bar
                                                                {{ $metadata['utilization_percent'] > 90 ? 'bg-danger' : ($metadata['utilization_percent'] > 75 ? 'bg-warning' : 'bg-success') }}"
                                                                role="progressbar"
                                                                style="width: {{ min($metadata['utilization_percent'], 100) }}%"
                                                                aria-valuenow="{{ $metadata['utilization_percent'] }}"
                                                                aria-valuemin="0"
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">{{ format_indian_percentage($metadata['utilization_percent'], 1) }}</small>
                                                    </div>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($metadata)
                                                    <span class="badge bg-{{ $metadata['health']['color'] }}"
                                                          data-bs-toggle="tooltip"
                                                          data-bs-placement="top"
                                                          title="Health Score: {{ $metadata['health_score'] }}/100
@if(!empty($metadata['health']['factors']))
{{ implode(', ', $metadata['health']['factors']) }}
@endif">
                                                        <i data-feather="{{ $metadata['health']['icon'] }}" style="width: 14px; height: 14px;"></i>
                                                        {{ ucfirst($metadata['health_level']) }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($metadata && $metadata['last_report_date'])
                                                    <small class="text-muted">
                                                        {{ $metadata['last_report_date']->format('M d, Y') }}
                                                        <br>
                                                        <span class="text-muted">{{ $metadata['last_report_date']->diffForHumans() }}</span>
                                                    </small>
                                                @else
                                                    <small class="text-danger">No reports</small>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $isApproved = $project->status === ProjectStatus::APPROVED_BY_COORDINATOR || $project->status === ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR;
                                                    $isDraft = $project->status === ProjectStatus::DRAFT;
                                                    $isReverted = str_contains($project->status, 'reverted');
                                                    $isRejected = $project->status === ProjectStatus::REJECTED_BY_COORDINATOR;

                                                    $badgeColor = $isApproved ? 'success' : ($isDraft ? 'secondary' : ($isReverted ? 'danger' : ($isRejected ? 'danger' : 'warning')));
                                                @endphp
                                                <span class="badge bg-{{ $badgeColor }}">
                                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <a href="{{ route('projects.show', $project->project_id) }}"
                                                       class="btn btn-sm btn-primary">
                                                        View
                                                    </a>
                                                    @if(in_array($project->status, ProjectStatus::getEditableStatuses()))
                                                        <a href="{{ route('projects.edit', $project->project_id) }}"
                                                           class="btn btn-sm btn-warning">
                                                            Edit
                                                        </a>
                                                    @endif
                                                    @if($isApproved)
                                                        <a href="{{ route('monthly.report.create', $project->project_id) }}"
                                                           class="btn btn-sm btn-success">
                                                            Create Report
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <i data-feather="inbox" class="text-muted" style="width: 48px; height: 48px;"></i>
                                                <p class="text-muted mt-2 mb-0">No owned projects found.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination (owned) --}}
                        @if($ownedProjects->hasPages())
                            <div class="mt-3">
                                {{ $ownedProjects->links() }}
                            </div>
                        @endif

                        {{-- Results Summary --}}
                        <div class="mt-2">
                            <small class="text-muted">
                                Showing {{ $ownedProjects->firstItem() ?? 0 }} to {{ $ownedProjects->lastItem() ?? 0 }} of {{ $ownedProjects->total() }} owned projects
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Assigned Projects (In-Charge) — view-only, no Create Report --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i data-feather="users" class="me-2"></i>
                        Assigned Projects (In-Charge)
                        <span class="badge bg-secondary ms-2">{{ $inChargeCount ?? 0 }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Project ID</th>
                                    <th style="min-width: 200px;">Project Title</th>
                                    <th>Project Type</th>
                                    <th>Budget</th>
                                    <th>Expenses</th>
                                    <th>Utilization</th>
                                    <th>Health</th>
                                    <th>Last Report</th>
                                    <th>Status</th>
                                    <th style="min-width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inChargeProjects as $project)
                                    @php
                                        $metadata = $enhancedInChargeProjects[$project->project_id] ?? null;
                                        $isApproved = $project->status === ProjectStatus::APPROVED_BY_COORDINATOR || $project->status === ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR;
                                        $isDraft = $project->status === ProjectStatus::DRAFT;
                                        $isReverted = str_contains($project->status, 'reverted');
                                        $isRejected = $project->status === ProjectStatus::REJECTED_BY_COORDINATOR;
                                        $badgeColor = $isApproved ? 'success' : ($isDraft ? 'secondary' : ($isReverted ? 'danger' : ($isRejected ? 'danger' : 'warning')));
                                    @endphp
                                    <tr>
                                        <td><a href="{{ route('projects.show', $project->project_id) }}" class="text-primary text-decoration-none fw-bold" title="View Project">{{ $project->project_id }}</a></td>
                                        <td style="max-width: 300px; word-wrap: break-word; white-space: normal;"><div class="text-wrap" title="{{ $project->project_title }}">{{ $project->project_title }}</div></td>
                                        <td><span class="badge bg-secondary">{{ $project->project_type }}</span></td>
                                        <td>@if($metadata)<small class="text-muted">{{ format_indian_currency($metadata['budget'], 2) }}</small>@else<small class="text-muted">-</small>@endif</td>
                                        <td>@if($metadata)<small class="text-muted">{{ format_indian_currency($metadata['expenses'], 2) }}</small>@else<small class="text-muted">-</small>@endif</td>
                                        <td>
                                            @if($metadata)
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 60px; height: 20px;">
                                                        <div class="progress-bar {{ $metadata['utilization_percent'] > 90 ? 'bg-danger' : ($metadata['utilization_percent'] > 75 ? 'bg-warning' : 'bg-success') }}" role="progressbar" style="width: {{ min($metadata['utilization_percent'], 100) }}%" aria-valuenow="{{ $metadata['utilization_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <small class="text-muted">{{ format_indian_percentage($metadata['utilization_percent'], 1) }}</small>
                                                </div>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($metadata)
                                                <span class="badge bg-{{ $metadata['health']['color'] }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Health Score: {{ $metadata['health_score'] }}/100 @if(!empty($metadata['health']['factors'])){{ implode(', ', $metadata['health']['factors']) }}@endif">
                                                    <i data-feather="{{ $metadata['health']['icon'] }}" style="width: 14px; height: 14px;"></i> {{ ucfirst($metadata['health_level']) }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>@if($metadata && $metadata['last_report_date'])<small class="text-muted">{{ $metadata['last_report_date']->format('M d, Y') }}<br><span class="text-muted">{{ $metadata['last_report_date']->diffForHumans() }}</span></small>@else<small class="text-danger">No reports</small>@endif</td>
                                        <td><span class="badge bg-{{ $badgeColor }}">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="{{ route('projects.show', $project->project_id) }}" class="btn btn-sm btn-primary">View</a>
                                                @if(in_array($project->status, ProjectStatus::getEditableStatuses()))
                                                    <a href="{{ route('projects.edit', $project->project_id) }}" class="btn btn-sm btn-warning">Edit</a>
                                                @endif
                                                {{-- No Create Report for in-charge (view-only for reporting responsibility) --}}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center py-4"><i data-feather="inbox" class="text-muted" style="width: 48px; height: 48px;"></i><p class="text-muted mt-2 mb-0">No assigned (in-charge) projects.</p></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($inChargeProjects->hasPages())
                        <div class="mt-3">{{ $inChargeProjects->links() }}</div>
                    @endif
                    <div class="mt-2"><small class="text-muted">Showing {{ $inChargeProjects->firstItem() ?? 0 }} to {{ $inChargeProjects->lastItem() ?? 0 }} of {{ $inChargeProjects->total() }} in-charge projects</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Load dashboard layout preferences
    loadDashboardLayout();

    // Equalize heights for Action Items and Report Status Summary widgets
    window.equalizeWidgetHeights = function() {
        const actionItemsWidget = document.querySelector('[data-widget-id="action-items"] .equal-height-widget');
        const reportStatusWidget = document.querySelector('[data-widget-id="report-status-summary"] .equal-height-widget');

        if (actionItemsWidget && reportStatusWidget) {
            // Reset heights first to get natural height
            actionItemsWidget.style.height = 'auto';
            reportStatusWidget.style.height = 'auto';

            // Wait for layout to update, then get heights
            setTimeout(function() {
                const actionItemsHeight = actionItemsWidget.offsetHeight;
                const reportStatusHeight = reportStatusWidget.offsetHeight;
                const maxHeight = Math.max(actionItemsHeight, reportStatusHeight);

                // Apply the maximum height to both widgets (minimum 400px for better appearance)
                if (maxHeight > 0) {
                    const finalHeight = Math.max(maxHeight, 400);
                    actionItemsWidget.style.height = finalHeight + 'px';
                    reportStatusWidget.style.height = finalHeight + 'px';
                }
            }, 50);
        }
    };

    // Call equalize heights after page load
    equalizeWidgetHeights();

    // Re-equalize heights after a short delay to ensure all content is loaded
    setTimeout(equalizeWidgetHeights, 300);

    // Re-equalize on window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            equalizeWidgetHeights();
        }, 150);
    });

    // Initialize SortableJS for widget reordering (if library is available)
    if (typeof Sortable !== 'undefined') {
        const widgetContainer = document.getElementById('dashboardWidgetsContainer');
        if (widgetContainer) {
            // Make widgets sortable with drag handle
            window.widgetSortable = new Sortable(widgetContainer, {
                animation: 150,
                handle: '.widget-drag-handle', // Only drag by handle
                ghostClass: 'widget-ghost',
                chosenClass: 'widget-chosen',
                filter: '.widget-container[style*="display: none"]', // Ignore hidden widgets
                forceFallback: false, // Use native HTML5 drag if available
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onStart: function(evt) {
                    // Show all drag handles when dragging starts
                    document.querySelectorAll('.widget-drag-handle').forEach(handle => {
                        handle.style.opacity = '1';
                    });

                    // Initialize feather icons if needed
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                },
                onEnd: function(evt) {
                    // Hide drag handles after dragging ends (restore hover behavior)
                    document.querySelectorAll('.widget-drag-handle').forEach(handle => {
                        handle.style.removeProperty('opacity'); // Remove inline style to allow CSS hover
                    });

                    // Save order
                    saveWidgetOrder();

                    // Resize charts and equalize widget heights after reorder
                    setTimeout(() => {
                        resizeAllCharts();
                        equalizeWidgetHeights();
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                    }, 100);
                }
            });
        }
    } else {
        // Fallback message if SortableJS is not loaded
        console.warn('SortableJS not loaded. Widget reordering disabled.');
        // Hide drag handles if SortableJS is not available
        document.querySelectorAll('.widget-drag-handle').forEach(handle => {
            handle.style.display = 'none';
        });
    }

    // Function to resize all charts
    function resizeAllCharts() {
        if (typeof budgetUtilizationChart !== 'undefined' && budgetUtilizationChart) {
            budgetUtilizationChart.resize();
        }
        if (typeof budgetDistributionChart !== 'undefined' && budgetDistributionChart) {
            budgetDistributionChart.resize();
        }
        if (typeof budgetComparisonChart !== 'undefined' && budgetComparisonChart) {
            budgetComparisonChart.resize();
        }
        if (typeof budgetTrendsChart !== 'undefined' && budgetTrendsChart) {
            budgetTrendsChart.resize();
        }
        if (typeof reportStatusChart !== 'undefined' && reportStatusChart) {
            reportStatusChart.resize();
        }
        if (typeof reportTimelineChart !== 'undefined' && reportTimelineChart) {
            reportTimelineChart.resize();
        }
        if (typeof reportCompletionChart !== 'undefined' && reportCompletionChart) {
            reportCompletionChart.resize();
        }
        if (typeof projectStatusChart !== 'undefined' && projectStatusChart) {
            projectStatusChart.resize();
        }
        if (typeof projectTypeChart !== 'undefined' && projectTypeChart) {
            projectTypeChart.resize();
        }
        if (typeof healthChart !== 'undefined' && healthChart) {
            healthChart.resize();
        }
    }

    // Handle window resize for charts
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(resizeAllCharts, 250);
    });
});

// Dashboard Customization Functions
function toggleDashboardCustomization() {
    const panel = document.getElementById('dashboardCustomizationPanel');
    if (panel) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }
}

function toggleWidget(widgetId, isVisible) {
    const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
    if (widget) {
        if (isVisible) {
            widget.style.display = '';
            // Restore original column classes if needed
            if (!widget.classList.contains('widget-container')) {
                widget.classList.add('widget-container');
            }
        } else {
            widget.style.display = 'none';
        }

        // Resize charts and equalize widget heights after toggling
        setTimeout(() => {
            resizeAllCharts();
            equalizeWidgetHeights();
        }, 100);

        saveWidgetPreferences();
    }
}

function saveWidgetPreferences() {
    const preferences = {
        visibleWidgets: [],
        widgetOrder: []
    };

    // Get order from container (current DOM order)
    const container = document.getElementById('dashboardWidgetsContainer');
    if (container) {
        const orderedIds = Array.from(container.children)
            .filter(child => child.classList.contains('widget-container'))
            .map(child => child.getAttribute('data-widget-id'))
            .filter(id => id);
        preferences.widgetOrder = orderedIds;
    }

    // Get visible widgets
    document.querySelectorAll('.widget-container').forEach(function(widget) {
        const widgetId = widget.getAttribute('data-widget-id');
        if (widgetId && widget.style.display !== 'none') {
            preferences.visibleWidgets.push(widgetId);
        }
    });

    // Save to localStorage
    localStorage.setItem('executor_dashboard_preferences', JSON.stringify(preferences));
}

function loadDashboardLayout() {
    const savedPreferences = localStorage.getItem('executor_dashboard_preferences');

    if (savedPreferences) {
        try {
            const preferences = JSON.parse(savedPreferences);

            // Hide widgets not in visible list
            if (preferences.visibleWidgets && preferences.visibleWidgets.length > 0) {
                document.querySelectorAll('.widget-container').forEach(function(widget) {
                    const widgetId = widget.getAttribute('data-widget-id');
                    if (!preferences.visibleWidgets.includes(widgetId)) {
                        widget.style.display = 'none';
                    }

                    // Update toggle checkbox
                    const checkbox = document.getElementById(`widget-${widgetId}`);
                    if (checkbox) {
                        checkbox.checked = preferences.visibleWidgets.includes(widgetId);
                    }
                });
            }

            // Reorder widgets if order is saved
            if (preferences.widgetOrder && preferences.widgetOrder.length > 0 && typeof Sortable !== 'undefined') {
                const container = document.getElementById('dashboardWidgetsContainer');
                if (container) {
                    preferences.widgetOrder.forEach(function(widgetId) {
                        const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
                        if (widget) {
                            container.appendChild(widget);
                        }
                    });
                }
            }
        } catch (e) {
            console.error('Error loading dashboard preferences:', e);
        }
    }
}

function saveWidgetOrder() {
    saveWidgetPreferences(); // Reuse the same function
}

function saveDashboardLayout() {
    saveWidgetPreferences();

    // Show success message (using Bootstrap alert if available, otherwise alert)
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <i data-feather="check-circle" style="width: 16px; height: 16px;"></i>
        Dashboard layout saved successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);

    // Initialize feather icons in alert
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);

    // Close customization panel
    toggleDashboardCustomization();
}

function resetDashboardLayout() {
    if (confirm('Are you sure you want to reset the dashboard layout to default?')) {
        // Clear saved preferences
        localStorage.removeItem('executor_dashboard_preferences');

        // Reset all widgets to visible
        document.querySelectorAll('.widget-container').forEach(function(widget) {
            widget.style.display = '';
            const widgetId = widget.getAttribute('data-widget-id');
            const checkbox = document.getElementById(`widget-${widgetId}`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });

        // Reset order (reload page or reset DOM order)
        location.reload();
    }
}

// Make functions available globally
window.toggleDashboardCustomization = toggleDashboardCustomization;
window.toggleWidget = toggleWidget;
window.saveDashboardLayout = saveDashboardLayout;
window.resetDashboardLayout = resetDashboardLayout;
window.resizeAllCharts = resizeAllCharts;
</script>
@endpush

<style>
/* Dashboard Customization Styles */
.widget-container {
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    margin-bottom: 1.5rem;
}

.widget-container > .card {
    position: relative;
}

.widget-drag-handle {
    opacity: 0;
    transition: opacity 0.2s ease, background-color 0.2s ease;
    background-color: rgba(0, 0, 0, 0.5);
    border-radius: 4px;
    padding: 4px;
    cursor: move;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
}

.widget-container:hover .widget-drag-handle {
    opacity: 0.7 !important;
}

.widget-container:hover .widget-drag-handle:hover {
    opacity: 1 !important;
    background-color: rgba(101, 113, 255, 0.8);
}

.widget-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.widget-ghost {
    opacity: 0.4;
    background-color: rgba(101, 113, 255, 0.1);
    border: 2px dashed #6571ff;
}

.widget-chosen {
    background-color: rgba(101, 113, 255, 0.2);
    border: 2px solid #6571ff;
    cursor: move;
}

.widget-drag-handle {
    background-color: rgba(0, 0, 0, 0.5);
    border-radius: 4px;
    padding: 4px;
}

.widget-drag-handle:hover {
    background-color: rgba(0, 0, 0, 0.7);
}

/* Customization Panel Styles */
#dashboardCustomizationPanel .list-group-item {
    transition: background-color 0.2s ease;
}

#dashboardCustomizationPanel .list-group-item:hover {
    background-color: rgba(101, 113, 255, 0.1) !important;
}

.form-check-input:checked {
    background-color: #6571ff;
    border-color: #6571ff;
}

.form-check-input:focus {
    border-color: #6571ff;
    box-shadow: 0 0 0 0.2rem rgba(101, 113, 255, 0.25);
}
</style>

<style>
/* Dashboard Layout Optimization for Charts */
@media (max-width: 768px) {
    .chart-container {
        min-height: 250px;
    }

    .chart-container > div {
        min-height: 250px !important;
    }
}

@media (min-width: 769px) and (max-width: 992px) {
    .chart-container {
        min-height: 280px;
    }

    .chart-container > div {
        min-height: 280px !important;
    }
}

@media (min-width: 993px) {
    .chart-container {
        min-height: 300px;
    }

    .chart-container > div {
        min-height: 300px !important;
    }
}

/* Widget spacing optimization */
.card.mb-4 {
    margin-bottom: 1.5rem !important;
}

/* Chart button group responsiveness */
@media (max-width: 576px) {
    .btn-group-sm {
        flex-direction: column;
        width: 100%;
    }

    .btn-group-sm .btn {
        width: 100%;
        margin-bottom: 0.25rem;
    }
}

/* Table responsiveness improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }

    .table th,
    .table td {
        padding: 0.5rem;
    }
}

/* Project Title text wrapping */
.table td[style*="word-wrap"],
.table td[style*="max-width"] {
    word-wrap: break-word;
    white-space: normal;
    overflow-wrap: break-word;
    hyphens: auto;
}

.table th[style*="min-width"] {
    white-space: nowrap;
}

/* Action buttons styling for text buttons */
.d-flex.flex-wrap.gap-1 {
    gap: 0.25rem !important;
}

.d-flex.flex-wrap.gap-1 .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    white-space: nowrap;
    min-width: fit-content;
}

/* Clickable Project and Report ID links styling */
a.text-primary.text-decoration-none.fw-bold,
a.text-info.text-decoration-none.fw-bold,
a.text-white.text-decoration-none {
    transition: all 0.2s ease;
}

a.text-primary.text-decoration-none.fw-bold:hover,
a.text-info.text-decoration-none.fw-bold:hover,
a.text-white.text-decoration-none:hover {
    text-decoration: underline !important;
    opacity: 0.8;
}

/* Widget specific link styling */
.widget-container a.text-info.text-decoration-none:hover,
.widget-container a.text-primary.text-decoration-none:hover {
    text-decoration: underline !important;
    opacity: 0.9;
}

/* Budget summary cards styling */
.card.bg-primary.bg-opacity-25,
.card.bg-success.bg-opacity-25,
.card.bg-info.bg-opacity-25 {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card.bg-primary.bg-opacity-25:hover,
.card.bg-success.bg-opacity-25:hover,
.card.bg-info.bg-opacity-25:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

/* Equal height widgets - Action Items and Report Status Summary */
#dashboardWidgetsContainer.row {
    display: flex;
    flex-wrap: wrap;
}

/* Make widget containers stretch to equal height */
.widget-container {
    display: flex;
    flex-direction: column;
}

.widget-container[data-widget-id="action-items"],
.widget-container[data-widget-id="report-status-summary"] {
    display: flex;
    flex-direction: column;
}

/* Equal height widget cards */
.equal-height-widget {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.equal-height-widget .card-header {
    flex-shrink: 0;
}

.equal-height-widget .card-body {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0; /* Important for flexbox scrolling */
    overflow: hidden; /* Prevent content overflow from breaking layout */
}

/* Scrollable Action Items widget content */
.action-items-scrollable {
    overflow-y: auto;
    overflow-x: hidden;
    max-height: 100%;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.3) rgba(0, 0, 0, 0.2);
    padding-right: 8px; /* Space for scrollbar */
}

/* Webkit scrollbar styling for Action Items */
.action-items-scrollable::-webkit-scrollbar {
    width: 8px;
}

.action-items-scrollable::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
}

.action-items-scrollable::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
}

.action-items-scrollable::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

/* Ensure widgets maintain equal height on same row (md and up) */
@media (min-width: 768px) {
    .widget-container[data-widget-id="action-items"] .equal-height-widget,
    .widget-container[data-widget-id="report-status-summary"] .equal-height-widget {
        min-height: 500px; /* Set minimum height for equal appearance */
    }

    /* Make sure both widgets align when side by side */
    .widget-container[data-widget-id="action-items"]:not(:last-child),
    .widget-container[data-widget-id="report-status-summary"]:not(:last-child) {
        margin-bottom: 0;
    }
}

/* Mobile: stack widgets vertically */
@media (max-width: 767px) {
    .widget-container[data-widget-id="action-items"] .equal-height-widget,
    .widget-container[data-widget-id="report-status-summary"] .equal-height-widget {
        min-height: auto;
    }

    .action-items-scrollable {
        max-height: 400px; /* Limit height on mobile */
    }
}
</style>
@endsection
