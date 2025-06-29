@extends('executor.dashboard')

@section('content')
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

                    <!-- Total Summary -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-white card bg-primary">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Budget</h5>
                                        <h3 class="card-text">₱{{ number_format($budgetSummaries['total']['total_budget'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-white card bg-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Expenses</h5>
                                        <h3 class="card-text">₱{{ number_format($budgetSummaries['total']['total_expenses'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-white card bg-info">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Remaining</h5>
                                        <h3 class="card-text">₱{{ number_format($budgetSummaries['total']['total_remaining'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
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
                                        <td>{{ number_format($totalAmount, 2) }}</td>
                                        <td>{{ number_format($totalExpenses, 2) }}</td>
                                        <td>{{ number_format($expensesThisMonth, 2) }}</td>
                                        <td>{{ number_format($balanceAmount, 2) }}</td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('monthly.report.show', $report->report_id) }}" class="btn btn-primary btn-sm">View</a>
                                            @if(in_array($report->status, ['draft', 'reverted_by_provincial', 'reverted_by_coordinator']))
                                                <a href="{{ route('monthly.report.edit', $report->report_id) }}" class="btn btn-warning btn-sm">Edit</a>
                                            @endif

                                            @if($report->status === 'underwriting')
                                                <form method="POST" action="{{ route('executor.report.submit', $report->report_id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to submit this report to provincial?')">
                                                        Submit to Provincial
                                                    </button>
                                                </form>
                                            @endif

                                            @if($report->status === 'reverted_by_provincial' && $report->revert_reason)
                                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Revert Reason: {{ $report->revert_reason }}">
                                                    View Reason
                                                </button>
                                            @endif
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
</div>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
