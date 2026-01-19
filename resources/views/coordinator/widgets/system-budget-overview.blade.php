@php
    // Get budget data (filtered based on request parameters)
    $budgetData = $systemBudgetOverviewData ?? [];
    $total = $budgetData['total'] ?? [
        'budget' => 0,
        'approved_expenses' => 0,
        'unapproved_expenses' => 0,
        'expenses' => 0,
        'remaining' => 0,
        'utilization' => 0
    ];
    $byProjectType = $budgetData['by_project_type'] ?? [];
    $byProvince = $budgetData['by_province'] ?? [];
    $byCenter = $budgetData['by_center'] ?? [];
    $topProjects = $budgetData['top_projects_by_budget'] ?? [];

    // Calculate percentages for progress bar
    $totalBudget = $total['budget'] ?? 0;
    $approvedExpenses = $total['approved_expenses'] ?? 0;
    $unapprovedExpenses = $total['unapproved_expenses'] ?? 0;
    $totalRemaining = $total['remaining'] ?? 0;

    $approvedPercent = $totalBudget > 0 ? ($approvedExpenses / $totalBudget) * 100 : 0;
    $unapprovedPercent = $totalBudget > 0 ? ($unapprovedExpenses / $totalBudget) * 100 : 0;
    $remainingPercent = max(0, min(100, 100 - $approvedPercent));

    // Filter options are passed from the main coordinator dashboard view
    // These variables should be available: $provinces, $centers, $projectTypes, $parents
@endphp

<div class="card mb-4 widget-card" data-widget-id="system-budget-overview">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="dollar-sign" class="me-2"></i>Project Budgets Overview
        </h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="system-budget-overview" title="Minimize">
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="card-body widget-content">
        {{-- Filter Form (Always Visible - Like Provincial Dashboard) --}}
        <form method="GET" action="{{ route('coordinator.dashboard') }}" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="budget_filter_province" class="form-label">Province</label>
                    <select name="province" id="budget_filter_province" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Provinces</option>
                        @if(isset($provinces))
                            @foreach($provinces as $province)
                                <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                    {{ $province }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="budget_filter_center" class="form-label">Center</label>
                    <select name="center" id="budget_filter_center" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Centers</option>
                        @if(isset($centers))
                            @foreach($centers as $center)
                                <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                    {{ $center }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="budget_filter_project_type" class="form-label">Project Type</label>
                    <select name="project_type" id="budget_filter_project_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Project Types</option>
                        @if(isset($projectTypes))
                            @foreach($projectTypes as $type)
                                <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="budget_filter_provincial" class="form-label">Provincial</label>
                    <select name="parent_id" id="budget_filter_provincial" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Provincials</option>
                        @if(isset($parents))
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->name }} ({{ $parent->province }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i data-feather="filter" style="width: 14px; height: 14px;"></i> Apply Filters
                    </button>
                    <a href="{{ route('coordinator.dashboard') }}" class="btn btn-secondary btn-sm">
                        <i data-feather="refresh-cw" style="width: 14px; height: 14px;"></i> Reset
                    </a>
                </div>
            </div>
        </form>

        {{-- Active Filters Display (Always Visible When Filters Are Active) --}}
        @if(request('province') || request('center') || request('project_type') || request('parent_id'))
            <div class="alert alert-info mb-4">
                <strong>Active Filters:</strong>
                @if(request('province'))
                    <span class="badge badge-success me-2">Province: {{ request('province') }}</span>
                @endif
                @if(request('center'))
                    <span class="badge badge-success me-2">Center: {{ request('center') }}</span>
                @endif
                @if(request('project_type'))
                    <span class="badge badge-info me-2">Project Type: {{ request('project_type') }}</span>
                @endif
                @if(request('parent_id') && isset($parents))
                    @php
                        $selectedProvincial = $parents->firstWhere('id', request('parent_id'));
                    @endphp
                    @if($selectedProvincial)
                        <span class="badge badge-warning me-2">Provincial: {{ $selectedProvincial->name }}</span>
                    @endif
                @endif
                <a href="{{ route('coordinator.dashboard') }}" class="btn btn-sm btn-outline-secondary float-end">Clear All</a>
            </div>
        @endif

        {{-- Data Display Section --}}
        @if(empty($budgetData) || (empty($total['budget']) && empty($total['expenses'])))
            {{-- Empty State --}}
            <div class="text-center py-5">
                <div class="mb-3">
                    <i data-feather="inbox" style="width: 48px; height: 48px; color: #ccc;"></i>
                </div>
                <h5 class="text-muted">No Budget Data Available</h5>
                <p class="text-muted">
                    @if(request('province') || request('center') || request('project_type') || request('parent_id'))
                        No approved projects with budget information match the selected filters. Try adjusting your filters or clear them to see all data.
                    @else
                        There are no approved projects with budget information yet.
                    @endif
                </p>
            </div>
        @else
            {{-- Summary Cards (Like Executor Dashboard) --}}
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card bg-primary bg-opacity-25 border-primary h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Total Budget</small>
                                    <h4 class="mb-0 text-white">{{ format_indian_currency($totalBudget, 2) }}</h4>
                                </div>
                                <div class="text-primary">
                                    <i data-feather="dollar-sign" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card bg-success bg-opacity-25 border-success h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Approved Expenses</small>
                                    <h4 class="mb-0 text-white">{{ format_indian_currency($approvedExpenses, 2) }}</h4>
                                    <small class="text-muted">Coordinator approved</small>
                                </div>
                                <div class="text-success">
                                    <i data-feather="check-circle" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card bg-warning bg-opacity-25 border-warning h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Unapproved Expenses</small>
                                    <h4 class="mb-0 text-white">{{ format_indian_currency($unapprovedExpenses, 2) }}</h4>
                                    <small class="text-muted">In pipeline / Pending approval</small>
                                </div>
                                <div class="text-warning">
                                    <i data-feather="clock" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card bg-info bg-opacity-25 border-info h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Total Remaining</small>
                                    <h4 class="mb-0 text-white">{{ format_indian_currency($totalRemaining, 2) }}</h4>
                                    <small class="text-muted">Based on approved expenses</small>
                                </div>
                                <div class="text-info">
                                    <i data-feather="trending-up" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Budget Utilization Progress Bar (Like Executor Dashboard) --}}
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">
                        <i data-feather="trending-up" style="width: 14px; height: 14px;" class="me-1"></i>
                        Budget Utilization (Based on Approved Expenses)
                    </small>
                    <small class="text-muted">
                        <span class="text-success">
                            <i data-feather="check-circle" style="width: 12px; height: 12px;" class="me-1"></i>
                            Approved: {{ format_indian_percentage($approvedPercent, 1) }}
                        </span> |
                        <span class="text-warning">
                            <i data-feather="clock" style="width: 12px; height: 12px;" class="me-1"></i>
                            In Pipeline: {{ format_indian_percentage($unapprovedPercent, 1) }}
                        </span> |
                        <span class="text-info">
                            <i data-feather="trending-up" style="width: 12px; height: 12px;" class="me-1"></i>
                            Remaining: {{ format_indian_percentage($remainingPercent, 1) }}
                        </span>
                    </small>
                </div>
                {{-- Main Progress Bar: Approved vs Remaining --}}
                <div class="progress mb-2" style="height: 30px; border: 1px solid rgba(255,255,255,0.15);">
                    @if($approvedPercent > 0)
                        <div class="progress-bar bg-success"
                             style="width: {{ $approvedPercent }}%"
                             role="progressbar"
                             aria-valuenow="{{ $approvedPercent }}"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             title="Approved Expenses: {{ format_indian_currency($approvedExpenses, 2) }} ({{ format_indian_percentage($approvedPercent, 1) }} of total budget)">
                            @if($approvedPercent > 8)
                                <strong class="text-white">{{ format_indian_percentage($approvedPercent, 1) }} Approved</strong>
                            @elseif($approvedPercent > 0)
                                <span class="text-white">✓</span>
                            @endif
                        </div>
                    @endif
                    @if($remainingPercent > 0)
                        <div class="progress-bar bg-info"
                             style="width: {{ $remainingPercent }}%"
                             role="progressbar"
                             aria-valuenow="{{ $remainingPercent }}"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             title="Remaining Budget: {{ format_indian_currency($totalRemaining, 2) }} ({{ format_indian_percentage($remainingPercent, 1) }} of total budget)">
                            @if($remainingPercent > 8)
                                <strong class="text-white">{{ format_indian_percentage($remainingPercent, 1) }} Remaining</strong>
                            @endif
                        </div>
                    @endif
                </div>
                {{-- Alert for unapproved expenses (doesn't reduce remaining budget) --}}
                @if($unapprovedPercent > 0)
                    <div class="alert alert-warning alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i data-feather="clock" style="width: 16px; height: 16px;" class="me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Expenses In Pipeline:</strong> {{ format_indian_currency($unapprovedExpenses, 2) }}
                                ({{ format_indian_percentage($unapprovedPercent, 1) }} of total budget) -
                                <span class="text-muted">These expenses are pending coordinator approval and do not reduce remaining budget until approved.</span>
                            </div>
                        </div>
                    </div>
                @endif
                <small class="text-muted mt-2 d-block">
                    <i data-feather="info" style="width: 12px; height: 12px;" class="me-1"></i>
                    <strong>Note:</strong> Remaining budget is calculated using approved expenses only. Unapproved expenses (in pipeline) are shown above separately and do not reduce available budget until approved.
                </small>
            </div>

            {{-- Budget Summary by Project Type Table (Like Provincial Dashboard) --}}
            <div class="mb-4">
                <h6 class="mb-3">
                    <i data-feather="pie-chart" class="me-1" style="width: 16px; height: 16px;"></i>
                    Budget Summary by Project Type
                </h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Project Type</th>
                                <th>Total Budget</th>
                                <th>Approved Expenses</th>
                                <th>Unapproved Expenses</th>
                                <th>Total Expenses</th>
                                <th>Remaining Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byProjectType as $type => $data)
                                @php
                                    $typeApprovedPercent = $data['budget'] > 0 ? ($data['approved_expenses'] ?? 0) / $data['budget'] * 100 : 0;
                                    $typeUnapprovedPercent = $data['budget'] > 0 ? ($data['unapproved_expenses'] ?? 0) / $data['budget'] * 100 : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ Str::limit($type, 40) }}</span>
                                    </td>
                                    <td>{{ format_indian_currency($data['budget'], 2) }}</td>
                                    <td>
                                        <span class="text-success fw-bold">{{ format_indian_currency($data['approved_expenses'] ?? 0, 2) }}</span>
                                        @if($data['budget'] > 0)
                                            <br><small class="text-muted">({{ format_indian_percentage($typeApprovedPercent, 1) }})</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-warning fw-bold">{{ format_indian_currency($data['unapproved_expenses'] ?? 0, 2) }}</span>
                                        @if($data['budget'] > 0)
                                            <br><small class="text-muted">({{ format_indian_percentage($typeUnapprovedPercent, 1) }})</small>
                                        @endif
                                    </td>
                                    <td>{{ format_indian_currency($data['expenses'] ?? ($data['approved_expenses'] ?? 0) + ($data['unapproved_expenses'] ?? 0), 2) }}</td>
                                    <td>
                                        <span class="text-info fw-bold">{{ format_indian_currency($data['remaining'], 2) }}</span>
                                        <br><small class="text-muted">Based on approved</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Budget Summary by Province Table (Coordinator Level) --}}
            @if(count($byProvince) > 0)
            <div class="mb-4">
                <h6 class="mb-3">
                    <i data-feather="map-pin" class="me-1" style="width: 16px; height: 16px;"></i>
                    Budget Summary by Province
                </h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Province</th>
                                <th>Total Budget</th>
                                <th>Approved Expenses</th>
                                <th>Unapproved Expenses</th>
                                <th>Total Expenses</th>
                                <th>Remaining Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byProvince as $province => $data)
                                @php
                                    $provinceApprovedPercent = $data['budget'] > 0 ? ($data['approved_expenses'] ?? 0) / $data['budget'] * 100 : 0;
                                    $provinceUnapprovedPercent = $data['budget'] > 0 ? ($data['unapproved_expenses'] ?? 0) / $data['budget'] * 100 : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $province }}</strong></td>
                                    <td>{{ format_indian_currency($data['budget'], 2) }}</td>
                                    <td>
                                        <span class="text-success fw-bold">{{ format_indian_currency($data['approved_expenses'] ?? 0, 2) }}</span>
                                        @if($data['budget'] > 0)
                                            <br><small class="text-muted">({{ format_indian_percentage($provinceApprovedPercent, 1) }})</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-warning fw-bold">{{ format_indian_currency($data['unapproved_expenses'] ?? 0, 2) }}</span>
                                        @if($data['budget'] > 0)
                                            <br><small class="text-muted">({{ format_indian_percentage($provinceUnapprovedPercent, 1) }})</small>
                                        @endif
                                    </td>
                                    <td>{{ format_indian_currency($data['expenses'] ?? 0, 2) }}</td>
                                    <td>
                                        <span class="text-info fw-bold">{{ format_indian_currency($data['remaining'], 2) }}</span>
                                        <br><small class="text-muted">Based on approved</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Budget Summary by Center Table (Like Provincial Dashboard) --}}
            @if(count($byCenter) > 0)
            <div class="mb-4">
                <h6 class="mb-3">
                    <i data-feather="map-pin" class="me-1" style="width: 16px; height: 16px;"></i>
                    Budget Summary by Center
                </h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Center</th>
                                <th>Total Budget</th>
                                <th>Approved Expenses</th>
                                <th>Unapproved Expenses</th>
                                <th>Total Expenses</th>
                                <th>Remaining Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byCenter as $center => $data)
                                <tr>
                                    <td><strong>{{ $center }}</strong></td>
                                    <td>{{ format_indian_currency($data['budget'], 2) }}</td>
                                    <td>
                                        <span class="text-success fw-bold">{{ format_indian_currency($data['approved_expenses'] ?? 0, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-warning fw-bold">{{ format_indian_currency($data['unapproved_expenses'] ?? 0, 2) }}</span>
                                    </td>
                                    <td>{{ format_indian_currency($data['expenses'] ?? 0, 2) }}</td>
                                    <td>
                                        <span class="text-info fw-bold">{{ format_indian_currency($data['remaining'], 2) }}</span>
                                        <br><small class="text-muted">Based on approved</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Top Projects by Budget (Optional - for detailed analysis) --}}
            @if(count($topProjects) > 0)
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Top 10 Projects by Budget</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Rank</th>
                                            <th>Project ID</th>
                                            <th>Project Title</th>
                                            <th>Type</th>
                                            <th>Province</th>
                                            <th>Budget</th>
                                            <th>Expenses</th>
                                            <th>Remaining</th>
                                            <th>Utilization</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topProjects as $index => $project)
                                            <tr>
                                                <td>
                                                    @if($index < 3)
                                                        <span class="badge badge-{{ $index === 0 ? 'warning' : ($index === 1 ? 'info' : 'secondary') }}">
                                                            #{{ $index + 1 }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">#{{ $index + 1 }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('coordinator.projects.show', $project['project_id']) }}"
                                                       class="text-primary font-weight-bold">
                                                        {{ $project['project_id'] }}
                                                    </a>
                                                </td>
                                                <td><small>{{ Str::limit($project['project_title'], 40) }}</small></td>
                                                <td><small>{{ Str::limit($project['project_type'], 25) }}</small></td>
                                                <td><span class="badge badge-secondary">{{ $project['province'] }}</span></td>
                                                <td><small>{{ format_indian_currency($project['budget'], 2) }}</small></td>
                                                <td><small>{{ format_indian_currency($project['expenses'], 2) }}</small></td>
                                                <td><small>{{ format_indian_currency($project['remaining'], 2) }}</small></td>
                                                <td>
                                                    <div class="progress" style="height: 20px; width: 100px;">
                                                        <div class="progress-bar {{ $project['utilization'] >= 90 ? 'bg-danger' : ($project['utilization'] >= 75 ? 'bg-warning' : 'bg-success') }}"
                                                             style="width: {{ min($project['utilization'], 100) }}%"
                                                             title="{{ format_indian_percentage($project['utilization'], 1) }}">
                                                            {{ format_indian_percentage($project['utilization'], 1) }}
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Export Budget Data
    window.exportBudgetData = function() {
        alert('Export functionality will be implemented soon.');
    };

    // Toggle Budget Filters
    window.toggleBudgetFilters = function() {
        alert('Filter functionality will be implemented soon.');
    };

    // Re-initialize feather icons after content loads
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endpush
