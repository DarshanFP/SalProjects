@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    {{-- Success/Error/Warning Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ========================================
         SECTION 1: BUDGET OVERVIEW
         Budget summary cards and data tables
         ======================================== --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <h5 class="text-muted mb-3">Budget Overview</h5>
        </div>
    </div>

    {{-- Dashboard Customization Panel (Hidden by default, can be toggled) --}}
    <div class="row mb-4" id="dashboardSettingsRow" style="display: none;">
        <div class="col-md-12">
            @include('provincial.widgets.dashboard-settings')
        </div>
    </div>

    {{-- Budget Overview Section --}}
    <div class="row justify-content-center widget-card" data-widget-id="budget-overview">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Budget Summary & Details</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="budget-overview" title="Minimize">−</button>
                </div>
                <div class="card-body widget-content">
                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('provincial.dashboard') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Project Types</option>
                                    @foreach($projectTypes as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="{{ route('provincial.dashboard') }}" class="btn btn-secondary">Clear Filters</a>
                            </div>
                        </div>
                    </form>

                    <!-- Active Filters Display -->
                    @if(request('center') || request('role') || request('project_type'))
                    <div class="alert alert-info mb-4">
                        <strong>Active Filters:</strong>
                        @if(request('center'))
                            <span class="badge badge-success me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('role'))
                            <span class="badge badge-warning me-2">Role: {{ ucfirst(request('role')) }}</span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge badge-info me-2">Project Type: {{ request('project_type') }}</span>
                        @endif
                        <a href="{{ route('provincial.dashboard') }}" class="btn btn-sm btn-outline-secondary float-right">Clear All</a>
                    </div>
                    @endif

                    <!-- Budget Summary Cards -->
                    <div class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="card bg-primary bg-opacity-25 border-primary h-100">
                                    <div class="card-body p-3">
                                        <small class="text-muted d-block">Total Budget</small>
                                        <h4 class="mb-0 text-white">{{ format_indian_currency($budgetSummaries['total']['total_budget'], 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success bg-opacity-25 border-success h-100">
                                    <div class="card-body p-3">
                                        <small class="text-muted d-block">Approved Expenses</small>
                                        <h4 class="mb-0 text-white">{{ format_indian_currency($budgetSummaries['total']['approved_expenses'] ?? 0, 2) }}</h4>
                                        <small class="text-muted">Coordinator approved</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning bg-opacity-25 border-warning h-100">
                                    <div class="card-body p-3">
                                        <small class="text-muted d-block">Unapproved Expenses</small>
                                        <h4 class="mb-0 text-white">{{ format_indian_currency($budgetSummaries['total']['unapproved_expenses'] ?? 0, 2) }}</h4>
                                        <small class="text-muted">Pending approval</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info bg-opacity-25 border-info h-100">
                                    <div class="card-body p-3">
                                        <small class="text-muted d-block">Total Remaining</small>
                                        <h4 class="mb-0 text-white">{{ format_indian_currency($budgetSummaries['total']['total_remaining'], 2) }}</h4>
                                        <small class="text-muted">Based on approved expenses</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Budget Utilization Progress Bar --}}
                        @php
                            $totalBudget = $budgetSummaries['total']['total_budget'];
                            $approvedExpenses = $budgetSummaries['total']['approved_expenses'] ?? 0;
                            $unapprovedExpenses = $budgetSummaries['total']['unapproved_expenses'] ?? 0;
                            $totalRemaining = $budgetSummaries['total']['total_remaining'];

                            // Calculate percentages based on total budget
                            // Remaining budget = total_budget - approved_expenses (unapproved don't reduce available budget)
                            $approvedPercent = $totalBudget > 0 ? ($approvedExpenses / $totalBudget) * 100 : 0;
                            $unapprovedPercent = $totalBudget > 0 ? ($unapprovedExpenses / $totalBudget) * 100 : 0;
                            $remainingPercent = $totalBudget > 0 ? ($totalRemaining / $totalBudget) * 100 : 100;

                            // Ensure percentages are valid (remaining should equal 100 - approved)
                            $remainingPercent = max(0, min(100, 100 - $approvedPercent));
                        @endphp
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Budget Utilization (Based on Approved Expenses)</small>
                                <small class="text-muted">
                                    <span class="text-success">Approved: {{ format_indian_percentage($approvedPercent, 1) }}</span> |
                                    <span class="text-warning">Pending: {{ format_indian_percentage($unapprovedPercent, 1) }}</span> |
                                    <span class="text-info">Remaining: {{ format_indian_percentage($remainingPercent, 1) }}</span>
                                </small>
                            </div>
                            {{-- Main Progress Bar: Approved vs Remaining (unapproved shown separately) --}}
                            <div class="progress mb-2" style="height: 30px; border: 1px solid rgba(255,255,255,0.15);">
                                @if($approvedPercent > 0)
                                    <div class="progress-bar bg-success"
                                         style="width: {{ $approvedPercent }}%"
                                         role="progressbar"
                                         aria-valuenow="{{ $approvedPercent }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100"
                                         title="Approved Expenses: {{ format_indian_currency($approvedExpenses, 2) }} ({{ format_indian_percentage($approvedPercent, 1) }} of total budget) - Reduces remaining budget">
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
                                         title="Remaining Budget: {{ format_indian_currency($totalRemaining, 2) }} ({{ format_indian_percentage($remainingPercent, 1) }} of total budget) - Available after approved expenses">
                                        @if($remainingPercent > 8)
                                            <strong class="text-white">{{ format_indian_percentage($remainingPercent, 1) }} Remaining</strong>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            {{-- Separate indicator for unapproved expenses (doesn't reduce remaining budget) --}}
                            @if($unapprovedPercent > 0)
                                <div class="alert alert-warning alert-dismissible fade show mb-0" role="alert">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <strong>Unapproved Expenses:</strong> {{ format_indian_currency($unapprovedExpenses, 2) }}
                                            ({{ format_indian_percentage($unapprovedPercent, 1) }} of total budget) -
                                            <span class="text-muted">These expenses are pending coordinator approval and do not reduce remaining budget until approved.</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <small class="text-muted mt-2 d-block">
                                <strong>Note:</strong> Remaining budget is calculated using approved expenses only. Unapproved expenses are shown above separately and do not reduce available budget until approved by coordinator.
                            </small>
                        </div>
                    </div>

                    <!-- Budget by Project Type -->
                    <div class="mb-4">
                        <h6 class="mb-3">Budget Summary by Project Type</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
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
                                    @foreach($budgetSummaries['by_project_type'] as $type => $summary)
                                    @php
                                        $typeApprovedPercent = $summary['total_budget'] > 0 ? ($summary['approved_expenses'] ?? 0) / $summary['total_budget'] * 100 : 0;
                                        $typeUnapprovedPercent = $summary['total_budget'] > 0 ? ($summary['unapproved_expenses'] ?? 0) / $summary['total_budget'] * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ $type }}</span>
                                        </td>
                                        <td>{{ format_indian_currency($summary['total_budget'], 2) }}</td>
                                        <td>
                                            <span class="text-success fw-bold">{{ format_indian_currency($summary['approved_expenses'] ?? 0, 2) }}</span>
                                            @if($summary['total_budget'] > 0)
                                                <br><small class="text-muted">({{ format_indian_percentage($typeApprovedPercent, 1) }})</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-warning fw-bold">{{ format_indian_currency($summary['unapproved_expenses'] ?? 0, 2) }}</span>
                                            @if($summary['total_budget'] > 0)
                                                <br><small class="text-muted">({{ format_indian_percentage($typeUnapprovedPercent, 1) }})</small>
                                            @endif
                                        </td>
                                        <td>{{ format_indian_currency($summary['total_expenses'] ?? 0, 2) }}</td>
                                        <td>
                                            <span class="text-info fw-bold">{{ format_indian_currency($summary['total_remaining'], 2) }}</span>
                                            <br><small class="text-muted">Based on approved</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Budget by Center -->
                    <div class="mb-4">
                        <h6 class="mb-3">Budget Summary by Center</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
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
                                    @foreach($budgetSummaries['by_center'] as $center => $summary)
                                    <tr>
                                        <td><strong>{{ $center }}</strong></td>
                                        <td>{{ format_indian_currency($summary['total_budget'], 2) }}</td>
                                        <td>
                                            <span class="text-success fw-bold">{{ format_indian_currency($summary['approved_expenses'] ?? 0, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-warning fw-bold">{{ format_indian_currency($summary['unapproved_expenses'] ?? 0, 2) }}</span>
                                        </td>
                                        <td>{{ format_indian_currency($summary['total_expenses'] ?? 0, 2) }}</td>
                                        <td>
                                            <span class="text-info fw-bold">{{ format_indian_currency($summary['total_remaining'], 2) }}</span>
                                            <br><small class="text-muted">Based on approved</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Debug Information -->
                    {{-- @if(config('app.debug'))
                    <div class="alert alert-info">
                        <strong>Debug Info:</strong><br>
                        Selected Center: {{ request('center') ?: 'None' }}<br>
                        Selected Role: {{ request('role') ?: 'None' }}<br>
                        Selected Project Type: {{ request('project_type') ?: 'None' }}<br>
                        Available Centers: {{ $centers->count() }}<br>
                        Available Roles: {{ count($roles) }}
                    </div>
                    @endif --}}
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         SECTION 2: ACTIONABLE WIDGETS
         Widgets where Provincial User can take actions (Approve/Revert/Manage)
         ======================================== --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <h5 class="text-muted mb-3">Actions Required</h5>
        </div>
    </div>

    <div class="row mb-4">
        {{-- Pending Approvals Widget --}}
        <div class="col-md-12 mb-4">
            @include('provincial.widgets.pending-approvals')
        </div>
    </div>

    <div class="row mb-4">
        {{-- Approval Queue Widget --}}
        <div class="col-md-12 mb-4">
            @include('provincial.widgets.approval-queue')
        </div>
    </div>

    <div class="row mb-4">
        {{-- Team Overview Widget (Actionable - Manage Team) --}}
        <div class="col-md-12 mb-4">
            @include('provincial.widgets.team-overview')
        </div>
    </div>

    {{-- ========================================
         SECTION 3: CHARTS & ANALYTICS
         Visual analytics and performance charts
         ======================================== --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <h5 class="text-muted mb-3">Analytics & Performance</h5>
        </div>
    </div>

    <div class="row mb-4">
        {{-- Team Performance Summary Widget --}}
        <div class="col-md-12 mb-4">
            @include('provincial.widgets.team-performance')
        </div>
    </div>

    <div class="row mb-4">
        {{-- Team Budget Overview Widget (Enhanced) --}}
        <div class="col-md-12 mb-4">
            @include('provincial.widgets.team-budget-overview')
        </div>
    </div>

    <div class="row mb-4">
        {{-- Center Performance Comparison Widget --}}
        <div class="col-md-12 mb-4">
            @include('provincial.widgets.center-comparison')
        </div>
    </div>

    {{-- ========================================
         SECTION 4: ACTIVITY FEED
         Recent team activities and timeline
         ======================================== --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <h5 class="text-muted mb-3">Recent Activity</h5>
        </div>
    </div>

    <div class="row mb-4">
        {{-- Team Activity Feed Widget --}}
        <div class="col-md-12 mb-4">
            @include('provincial.widgets.team-activity-feed')
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize feather icons for widgets
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Load dashboard preferences (this will reorder and show/hide widgets)
    // This function is defined in dashboard-settings.blade.php
    if (typeof loadDashboardPreferences === 'function') {
        setTimeout(() => {
            loadDashboardPreferences();
        }, 500); // Wait for all widgets to load
    }

    // Debug form submission for budget filters
    const budgetFilterForm = document.querySelector('form[action="{{ route("provincial.dashboard") }}"]');
    if (budgetFilterForm) {
        budgetFilterForm.addEventListener('submit', function(e) {
            const formData = new FormData(budgetFilterForm);
            // Form submission handled by browser
        });
    }
});

// Initialize settings button icon
if (typeof feather !== 'undefined') {
    feather.replace();
}
</script>
@endpush

{{-- Dashboard Customization Button (Fixed Position) --}}
<button type="button"
        class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle shadow-lg"
        style="width: 56px; height: 56px; z-index: 1000;"
        onclick="toggleDashboardSettings()"
        title="Customize Dashboard">⚙</button>
@endsection
