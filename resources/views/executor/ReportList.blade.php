@extends('executor.dashboard')

@section('content')
@php
    use App\Constants\ProjectStatus;
    $editableStatuses = ProjectStatus::getEditableStatuses();
@endphp
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">My Project Reports</h4>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="mb-4">
                        <form method="GET" action="{{ route('executor.report.list') }}" class="row g-3">
                            <div class="col-md-4">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-select">
                                    <option value="">All Project Types</option>
                                    @foreach($projectTypes as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                <a href="{{ route('executor.report.list') }}" class="btn btn-secondary">Reset</a>
                            </div>
                        </form>
                    </div>

                    {{-- Upcoming Report Deadlines Section - Always show if there are deadlines --}}
                    @if(isset($upcomingDeadlines) && $upcomingDeadlines['total'] > 0)
                        <div class="mb-4" data-deadlines-section>
                            <div class="card bg-dark border-secondary">
                                <div class="card-header bg-secondary bg-opacity-25">
                                    <h5 class="mb-0 text-white">
                                        <i data-feather="calendar" class="me-2"></i>
                                        Upcoming Report Deadlines ({{ $upcomingDeadlines['total'] }})
                                    </h5>
                                    <small class="text-muted">Monthly report submission deadlines</small>
                                </div>
                                <div class="card-body">
                                    {{-- Overdue Report Deadlines --}}
                                    @if($upcomingDeadlines['overdue']->count() > 0)
                                        <div class="mb-4">
                                            <h6 class="text-danger mb-3">
                                                <i data-feather="alert-triangle" class="me-1" style="width: 16px; height: 16px;"></i>
                                                Overdue Report Deadlines ({{ $upcomingDeadlines['overdue']->count() }})
                                            </h6>
                                            <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                                                @foreach($upcomingDeadlines['overdue'] as $deadline)
                                                    <div class="list-group-item bg-dark border-danger">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-8">
                                                                <h6 class="mb-1 text-white">{{ $deadline['project']->project_title }}</h6>
                                                                <p class="mb-1 text-muted small">
                                                                    <span class="badge bg-danger me-2">Overdue</span>
                                                                    Monthly Report Deadline: {{ $deadline['report_month'] }}
                                                                </p>
                                                                <small class="text-danger">
                                                                    <i data-feather="clock" style="width: 12px; height: 12px;"></i>
                                                                    Report deadline: {{ $deadline['days_overdue'] ?? 0 }} day(s) overdue
                                                                </small>
                                                            </div>
                                                            <div class="col-md-4 text-end">
                                                                <a href="{{ route('monthly.report.create', $deadline['project']->project_id) }}"
                                                                   class="btn btn-sm btn-danger">
                                                                    <i data-feather="file-plus" style="width: 14px; height: 14px;"></i>
                                                                    Create Report
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- This Month Report Deadlines --}}
                                    @if($upcomingDeadlines['this_month']->count() > 0)
                                        <div class="mb-4">
                                            <h6 class="text-warning mb-3">
                                                <i data-feather="clock" class="me-1" style="width: 16px; height: 16px;"></i>
                                                Report Deadlines - Due This Month ({{ $upcomingDeadlines['this_month']->count() }})
                                            </h6>
                                            <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                                                @foreach($upcomingDeadlines['this_month'] as $deadline)
                                                    <div class="list-group-item bg-dark border-warning">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-8">
                                                                <h6 class="mb-1 text-white">{{ $deadline['project']->project_title }}</h6>
                                                                <p class="mb-1 text-muted small">
                                                                    <span class="badge bg-warning me-2">Due Soon</span>
                                                                    Monthly Report Deadline: {{ $deadline['report_month'] }}
                                                                </p>
                                                                <small class="text-warning">
                                                                    <i data-feather="calendar" style="width: 12px; height: 12px;"></i>
                                                                    Report deadline: Due in {{ $deadline['days_remaining'] }} day(s)
                                                                </small>
                                                            </div>
                                                            <div class="col-md-4 text-end">
                                                                <a href="{{ route('monthly.report.create', $deadline['project']->project_id) }}"
                                                                   class="btn btn-sm btn-warning">
                                                                    <i data-feather="file-plus" style="width: 14px; height: 14px;"></i>
                                                                    Create Report
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Next Month Report Deadlines --}}
                                    @if($upcomingDeadlines['next_month']->count() > 0)
                                        <div class="mb-3">
                                            <h6 class="text-info mb-3">
                                                <i data-feather="calendar" class="me-1" style="width: 16px; height: 16px;"></i>
                                                Report Deadlines - Due Next Month ({{ $upcomingDeadlines['next_month']->count() }})
                                            </h6>
                                            <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                                                @foreach($upcomingDeadlines['next_month'] as $deadline)
                                                    <div class="list-group-item bg-dark border-info">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-8">
                                                                <h6 class="mb-1 text-white">{{ $deadline['project']->project_title }}</h6>
                                                                <p class="mb-1 text-muted small">
                                                                    <span class="badge bg-info me-2">Upcoming</span>
                                                                    Monthly Report Deadline: {{ $deadline['report_month'] }}
                                                                </p>
                                                                <small class="text-info">
                                                                    <i data-feather="calendar" style="width: 12px; height: 12px;"></i>
                                                                    Report deadline: Due in {{ $deadline['days_remaining'] }} day(s)
                                                                </small>
                                                            </div>
                                                            <div class="col-md-4 text-end">
                                                                <a href="{{ route('monthly.report.create', $deadline['project']->project_id) }}"
                                                                   class="btn btn-sm btn-info">
                                                                    <i data-feather="file-plus" style="width: 14px; height: 14px;"></i>
                                                                    Create Report
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @elseif(!isset($upcomingDeadlines) || $upcomingDeadlines['total'] == 0)
                        {{-- No Deadlines Message --}}
                        <div class="mb-4">
                            <div class="alert alert-info">
                                <i data-feather="check-circle" style="width: 20px; height: 20px;" class="me-2"></i>
                                <strong>All report deadlines are up to date!</strong> No upcoming report deadlines at this time.
                            </div>
                        </div>
                    @endif

                    <!-- Total Summary -->
                    <div class="mb-4">
                        <h5 class="mb-3">Budget Summary</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-white card bg-primary">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Budget</h5>
                                        <h3 class="card-text">{{ format_indian_currency($budgetSummaries['total']['total_budget'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-white card bg-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Expenses</h5>
                                        <h3 class="card-text">{{ format_indian_currency($budgetSummaries['total']['total_expenses'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-white card bg-info">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Remaining</h5>
                                        <h3 class="card-text">{{ format_indian_currency($budgetSummaries['total']['total_remaining'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Table -->
                    <div class="mb-4">
                        <h5 class="mb-3">Existing Reports ({{ $reports->count() }})</h5>
                        @if($reports->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Project Title</th>
                                            <th>Project Type</th>
                                            <th>Total Amount</th>
                                            <th>Total Expenses</th>
                                            <th>Expenses This Month</th>
                                            <th>Balance Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reports as $report)
                                            @php
                                                // Summing up the account details
                                                $totalAmount = $report->accountDetails->sum('total_amount');
                                                $totalExpenses = $report->accountDetails->sum('total_expenses');
                                                $expensesThisMonth = $report->accountDetails->sum('expenses_this_month');
                                                $balanceAmount = $report->accountDetails->sum('balance_amount');

                                                // Get status label and badge class
                                                $statusLabel = $report->getStatusLabel();
                                                $statusBadgeClass = $report->getStatusBadgeClass();
                                            @endphp
                                            <tr>
                                                <td>{{ $report->report_id }}</td>
                                                <td>{{ $report->project_title }}</td>
                                                <td>{{ $report->project_type }}</td>
                                                <td>{{ format_indian($totalAmount, 2) }}</td>
                                                <td>{{ format_indian($totalExpenses, 2) }}</td>
                                                <td>{{ format_indian($expensesThisMonth, 2) }}</td>
                                                <td>{{ format_indian($balanceAmount, 2) }}</td>
                                                <td>
                                                    <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('monthly.report.show', $report->report_id) }}" class="btn btn-primary btn-sm">
                                                            <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                                            View
                                                        </a>
                                                        @if(in_array($report->status, $editableStatuses))
                                                            <a href="{{ route('monthly.report.edit', $report->report_id) }}" class="btn btn-warning btn-sm">
                                                                <i data-feather="edit" style="width: 14px; height: 14px;"></i>
                                                                Edit
                                                            </a>
                                                        @endif

                                                        @if($report->status === 'draft' || $report->isEditable())
                                                            <form method="POST" action="{{ route('executor.report.submit', $report->report_id) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to submit this report to provincial?')">
                                                                    <i data-feather="send" style="width: 14px; height: 14px;"></i>
                                                                    Submit
                                                                </button>
                                                            </form>
                                                        @endif

                                                        @if($report->status === ProjectStatus::REVERTED_BY_PROVINCIAL && $report->revert_reason)
                                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Revert Reason: {{ $report->revert_reason }}">
                                                                <i data-feather="info" style="width: 14px; height: 14px;"></i>
                                                                Reason
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            {{-- Empty State for Reports --}}
                            <div class="text-center py-5">
                                <i data-feather="file-text" class="text-muted" style="width: 64px; height: 64px;"></i>
                                <h5 class="text-muted mt-3">No Existing Reports Found</h5>
                                <p class="text-muted mb-4">
                                    @if(isset($upcomingDeadlines) && $upcomingDeadlines['total'] > 0)
                                        You haven't created any reports yet. However, you have {{ $upcomingDeadlines['total'] }} upcoming report deadline(s) listed above. Start by creating a report for those deadlines.
                                    @else
                                        You haven't created any reports yet. Start by creating a report for your projects when they become due.
                                    @endif
                                </p>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('executor.dashboard') }}" class="btn btn-primary">
                                        <i data-feather="arrow-left" style="width: 16px; height: 16px;"></i>
                                        Back to Dashboard
                                    </a>
                                    @if(isset($upcomingDeadlines) && $upcomingDeadlines['total'] > 0 && ($upcomingDeadlines['overdue']->count() > 0 || $upcomingDeadlines['this_month']->count() > 0))
                                        @php
                                            $firstDeadlineProject = $upcomingDeadlines['overdue']->first()['project']->project_id ?? ($upcomingDeadlines['this_month']->first()['project']->project_id ?? null);
                                        @endphp
                                        @if($firstDeadlineProject)
                                            <a href="{{ route('monthly.report.create', $firstDeadlineProject) }}"
                                               class="btn btn-success">
                                                <i data-feather="file-plus" style="width: 16px; height: 16px;"></i>
                                                Create Report for Upcoming Deadline
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('executor.dashboard') }}" class="btn btn-info">
                                            <i data-feather="calendar" style="width: 16px; height: 16px;"></i>
                                            View Upcoming Deadlines
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Initialize tooltips
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

    // Scroll to deadlines section if coming from widget link
    @if(request('show') == 'deadlines' && isset($upcomingDeadlines) && $upcomingDeadlines['total'] > 0)
        setTimeout(function() {
            document.querySelector('[data-deadlines-section]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    @endif
});
</script>
@endpush
@endsection
