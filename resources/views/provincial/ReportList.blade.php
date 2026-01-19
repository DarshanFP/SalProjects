{{-- resources/views/provincial/ReportList.blade.php --}}
@extends('provincial.dashboard')

@section('content')
@php
    use App\Constants\ProjectStatus;
    use App\Models\Reports\Monthly\DPReport;
@endphp
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Project Reports Overview</h4>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="mb-4">
                        <form method="GET" action="{{ route('provincial.report.list') }}" class="row g-3">
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
                                <a href="{{ route('provincial.report.list') }}" class="btn btn-secondary">Reset</a>
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
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Report ID</th>
                                    <th>Team Member</th>
                                    <th>Role</th>
                                    <th>Center</th>
                                    <th>Project Title</th>
                                    <th>Project Type</th>
                                    <th>Total Amount</th>
                                    <th>Total Expenses</th>
                                    <th>Expenses This Month</th>
                                    <th>Balance Amount</th>
                                    <th>Status</th>
                                    <th>Days Pending</th>
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

                                        // Calculate days pending for pending reports
                                        $daysPending = null;
                                        $urgencyClass = null;
                                        if (in_array($report->status, [DPReport::STATUS_SUBMITTED_TO_PROVINCIAL, DPReport::STATUS_REVERTED_BY_COORDINATOR])) {
                                            $daysPending = $report->created_at->diffInDays(now());
                                            $urgencyClass = $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success');
                                        }
                                    @endphp
                                    <tr class="align-middle">
                                        <td>
                                            <a href="{{ route('provincial.monthly.report.show', $report->report_id) }}"
                                               class="text-decoration-none fw-bold">
                                                {{ $report->report_id }}
                                            </a>
                                        </td>
                                        <td>
                                            <strong>{{ $report->user->name }}</strong>
                                            @if($report->user->email)
                                                <br><small class="text-muted">{{ $report->user->email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $report->user->role === 'executor' ? 'primary' : 'info' }}">
                                                {{ ucfirst($report->user->role) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $report->user->center ?? ($report->place ?? 'N/A') }}</small>
                                        </td>
                                        <td>
                                            <div class="text-wrap" style="max-width: 200px;">
                                                {{ $report->project_title }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $report->project_type }}</span>
                                        </td>
                                        <td class="text-end">{{ format_indian_currency($totalAmount, 2) }}</td>
                                        <td class="text-end">{{ format_indian_currency($totalExpenses, 2) }}</td>
                                        <td class="text-end">{{ format_indian_currency($expensesThisMonth, 2) }}</td>
                                        <td class="text-end">{{ format_indian_currency($balanceAmount, 2) }}</td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                            @if($report->status === DPReport::STATUS_REVERTED_BY_COORDINATOR && $report->revert_reason)
                                                <br>
                                                <button type="button"
                                                        class="p-0 mt-1 btn btn-link btn-sm"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="{{ $report->revert_reason }}">
                                                    <small>View Reason</small>
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            @if($daysPending !== null)
                                                <span class="badge bg-{{ $urgencyClass }}">
                                                    {{ $daysPending }} days
                                                </span>
                                                <br>
                                                <small class="text-muted">{{ $report->created_at->format('M d, Y') }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex-wrap gap-2 d-flex">
                                                <a href="{{ route('provincial.monthly.report.show', $report->report_id) }}"
                                                   class="btn btn-sm btn-primary">
                                                    View
                                                </a>

                                                @if($report->status === DPReport::STATUS_SUBMITTED_TO_PROVINCIAL)
                                                    <button type="button"
                                                            class="btn btn-sm btn-success"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#forwardModal{{ $report->report_id }}">
                                                        Forward
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#revertModal{{ $report->report_id }}">
                                                        Revert
                                                    </button>
                                                @endif

                                                @if($report->status === DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                                                    <span class="badge bg-info">Forwarded</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Forward Modal -->
                                    @if($report->status === ProjectStatus::SUBMITTED_TO_PROVINCIAL)
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
                                                            <textarea class="form-control auto-resize-textarea" id="revert_reason{{ $report->report_id }}" name="revert_reason" rows="3" required></textarea>
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

@push('scripts')
<script>
// Initialize tooltips and feather icons
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endpush
@endsection
