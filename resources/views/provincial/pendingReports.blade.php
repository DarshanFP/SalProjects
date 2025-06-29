{{-- resources/views/provincial/pendingReports.blade.php --}}
@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Pending Reports</h4>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="mb-4">
                        <form method="GET" action="{{ route('provincial.report.pending') }}" class="row g-3">
                            <div class="col-md-3">
                                <label for="place" class="form-label">Place</label>
                                <select name="place" id="place" class="form-select">
                                    <option value="">All Places</option>
                                    @foreach($places as $place)
                                        <option value="{{ $place }}" {{ request('place') == $place ? 'selected' : '' }}>
                                            {{ $place }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="user_id" class="form-label">Executor</label>
                                <select name="user_id" id="user_id" class="form-select">
                                    <option value="">All Executors</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                <a href="{{ route('provincial.report.pending') }}" class="btn btn-secondary">Reset</a>
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
                                    <th>Executor</th>
                                    <th>Place</th>
                                    <th>Project Title</th>
                                    <th>Total Amount</th>
                                    <th>Total Expenses</th>
                                    <th>Expenses This Month</th>
                                    <th>Balance Amount</th>
                                    <th>Type</th>
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
                                        <td>{{ $report->user->name }}</td>
                                        <td>{{ $report->place }}</td>
                                        <td>{{ $report->project_title }}</td>
                                        <td>{{ number_format($totalAmount, 2) }}</td>
                                        <td>{{ number_format($totalExpenses, 2) }}</td>
                                        <td>{{ number_format($expensesThisMonth, 2) }}</td>
                                        <td>{{ number_format($balanceAmount, 2) }}</td>
                                        <td>{{ $report->project_type }}</td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('provincial.monthly.report.show', $report->report_id) }}" class="btn btn-primary btn-sm">View</a>

                                            @if($report->status === 'submitted_to_provincial')
                                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $report->report_id }}">
                                                    Forward to Coordinator
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#revertModal{{ $report->report_id }}">
                                                    Revert to Executor
                                                </button>
                                            @endif

                                            @if($report->status === 'reverted_by_coordinator' && $report->revert_reason)
                                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Revert Reason: {{ $report->revert_reason }}">
                                                    View Reason
                                                </button>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Forward Modal -->
                                    @if($report->status === 'submitted_to_provincial')
                                    <div class="modal fade" id="forwardModal{{ $report->report_id }}" tabindex="-1" aria-labelledby="forwardModalLabel{{ $report->report_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="forwardModalLabel{{ $report->report_id }}">Forward Report to Coordinator</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('provincial.report.forward', $report->report_id) }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to forward this report to the coordinator?</p>
                                                        <p><strong>Report ID:</strong> {{ $report->report_id }}</p>
                                                        <p><strong>Project:</strong> {{ $report->project_title }}</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Forward to Coordinator</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Revert Modal -->
                                    <div class="modal fade" id="revertModal{{ $report->report_id }}" tabindex="-1" aria-labelledby="revertModalLabel{{ $report->report_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="revertModalLabel{{ $report->report_id }}">Revert Report to Executor</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('provincial.report.revert', $report->report_id) }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Report ID:</strong> {{ $report->report_id }}</p>
                                                        <p><strong>Project:</strong> {{ $report->project_title }}</p>
                                                        <div class="mb-3">
                                                            <label for="revert_reason{{ $report->report_id }}" class="form-label">Reason for Revert *</label>
                                                            <textarea class="form-control" id="revert_reason{{ $report->report_id }}" name="revert_reason" rows="3" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning">Revert to Executor</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
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
