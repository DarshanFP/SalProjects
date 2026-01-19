@extends('coordinator.dashboard')

@section('content')
@php
    use App\Constants\ProjectStatus;
    use App\Models\Reports\Monthly\DPReport;
@endphp
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="fp-text-center1 mb-0">Project Reports Overview</h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleBulkActions()">
                            Bulk Actions
                        </button>
                        <button type="button" class="btn btn-sm btn-info" onclick="toggleAdvancedFilters()">
                            Advanced Filters
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Success/Error Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
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
                            @if(session('bulk_errors'))
                                <ul class="mb-0 mt-2">
                                    @foreach(session('bulk_errors') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    {{-- Basic Filters --}}
                    <form method="GET" action="{{ route('coordinator.report.list') }}" id="filterForm">
                        <div class="mb-3 row">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control"
                                       placeholder="Report ID, Project Title..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-control">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ DPReport::$statusLabels[$status] ?? $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-control">
                                    <option value="">All Types</option>
                                    @foreach($projectTypes as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('coordinator.report.list') }}" class="btn btn-secondary">Clear</a>
                                </div>
                            </div>
                        </div>

                        {{-- Advanced Filters (Collapsible) --}}
                        <div id="advancedFilters" style="display: none;">
                            <div class="mb-3 row border-top pt-3">
                                <div class="col-md-3">
                                    <label for="provincial_id" class="form-label">Provincial</label>
                                    <select name="provincial_id" id="provincial_id" class="form-control">
                                        <option value="">All Provincials</option>
                                        @foreach($provincials as $provincial)
                                            <option value="{{ $provincial->id }}" {{ request('provincial_id') == $provincial->id ? 'selected' : '' }}>
                                                {{ $provincial->name }} ({{ $provincial->province }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="user_id" class="form-label">Executor/Applicant</label>
                                    <select name="user_id" id="user_id" class="form-control">
                                        <option value="">All Executors/Applicants</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->role }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="center" class="form-label">Center</label>
                                    <select name="center" id="center" class="form-control">
                                        <option value="">All Centers</option>
                                        @foreach($centers as $center)
                                            <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                                {{ $center }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="urgency" class="form-label">Urgency</label>
                                    <select name="urgency" id="urgency" class="form-control">
                                        <option value="">All</option>
                                        <option value="urgent" {{ request('urgency') == 'urgent' ? 'selected' : '' }}>Urgent (>7 days)</option>
                                        <option value="normal" {{ request('urgency') == 'normal' ? 'selected' : '' }}>Normal (3-7 days)</option>
                                        <option value="low" {{ request('urgency') == 'low' ? 'selected' : '' }}>Low (<3 days)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Bulk Actions (Hidden by default) --}}
                    <div id="bulkActions" class="mb-3 p-3 bg-light border rounded" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Selected Reports: <span id="selectedCount">0</span></strong>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-success btn-sm" onclick="bulkApprove()" id="bulkApproveBtn" disabled>
                                    Approve Selected
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="bulkRevert()" id="bulkRevertBtn" disabled>
                                    Revert Selected
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                                    Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Active Filters Display --}}
                    @if(request()->anyFilled(['search', 'province', 'status', 'project_type', 'provincial_id', 'user_id', 'center', 'urgency']))
                    <div class="alert alert-info mb-3">
                        <strong>Active Filters:</strong>
                        @if(request('search'))
                            <span class="badge badge-primary me-2">Search: {{ request('search') }}</span>
                        @endif
                        @if(request('province'))
                            <span class="badge badge-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('status'))
                            <span class="badge badge-info me-2">Status: {{ DPReport::$statusLabels[request('status')] ?? request('status') }}</span>
                        @endif
                        @if(request('project_type'))
                            <span class="badge badge-success me-2">Type: {{ request('project_type') }}</span>
                        @endif
                        @if(request('provincial_id'))
                            @php $selectedProvincial = $provincials->firstWhere('id', request('provincial_id')); @endphp
                            @if($selectedProvincial)
                                <span class="badge badge-warning me-2">Provincial: {{ $selectedProvincial->name }}</span>
                            @endif
                        @endif
                        @if(request('user_id'))
                            @php $selectedUser = $users->firstWhere('id', request('user_id')); @endphp
                            @if($selectedUser)
                                <span class="badge badge-secondary me-2">Executor: {{ $selectedUser->name }}</span>
                            @endif
                        @endif
                        @if(request('center'))
                            <span class="badge badge-dark me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('urgency'))
                            <span class="badge badge-{{ request('urgency') == 'urgent' ? 'danger' : (request('urgency') == 'normal' ? 'warning' : 'success') }} me-2">
                                Urgency: {{ ucfirst(request('urgency')) }}
                            </span>
                        @endif
                        <a href="{{ route('coordinator.report.list') }}" class="btn btn-sm btn-outline-secondary float-right">Clear All</a>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="reportsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAllReports" title="Select All">
                                    </th>
                                    <th>Report ID</th>
                                    <th>Project ID</th>
                                    <th>Project Title</th>
                                    <th>Executor/Applicant</th>
                                    <th>Province</th>
                                    <th>Center</th>
                                    <th>Provincial</th>
                                    <th>Total Amount</th>
                                    <th>Total Expenses</th>
                                    <th>Balance Amount</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Days Pending</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $report)
                                    @php
                                        // Summing up the account details
                                        $totalAmount = $report->accountDetails ? $report->accountDetails->sum('total_amount') : 0;
                                        $totalExpenses = $report->accountDetails ? $report->accountDetails->sum('total_expenses') : 0;
                                        $expensesThisMonth = $report->accountDetails ? $report->accountDetails->sum('expenses_this_month') : 0;
                                        $balanceAmount = $report->accountDetails ? $report->accountDetails->sum('balance_amount') : 0;

                                        // Get status label and badge class
                                        $statusLabel = $report->getStatusLabel();
                                        $statusBadgeClass = $report->getStatusBadgeClass();

                                    @endphp
                                    <tr>
                                        <td>
                                            @if($report->status === DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                                                <input type="checkbox" class="report-checkbox" value="{{ $report->report_id }}" data-report-id="{{ $report->report_id }}">
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('coordinator.monthly.report.show', $report->report_id) }}"
                                               class="text-primary font-weight-bold">
                                                {{ $report->report_id }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('coordinator.projects.show', $report->project_id) }}"
                                               class="text-info">
                                                {{ $report->project_id }}
                                            </a>
                                        </td>
                                        <td>{{ Str::limit($report->project_title ?? 'N/A', 40) }}</td>
                                        <td>
                                            {{ $report->user->name ?? 'N/A' }}
                                            <br>
                                            <small class="text-muted">({{ $report->user->role ?? 'N/A' }})</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">{{ $report->user->province ?? 'N/A' }}</span>
                                        </td>
                                        <td>{{ $report->user->center ?? 'N/A' }}</td>
                                        <td>
                                            @if($report->user->parent)
                                                {{ $report->user->parent->name }}
                                                <br>
                                                <small class="text-muted">({{ $report->user->parent->province ?? 'N/A' }})</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ format_indian_currency($totalAmount, 2) }}</td>
                                        <td>{{ format_indian_currency($totalExpenses, 2) }}</td>
                                        <td>{{ format_indian_currency($balanceAmount, 2) }}</td>
                                        <td>
                                            <small>{{ Str::limit($report->project_type ?? 'N/A', 30) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            @if($report->days_pending !== null)
                                                <span class="badge badge-{{ $report->urgency === 'urgent' ? 'danger' : ($report->urgency === 'normal' ? 'warning' : 'success') }}">
                                                    {{ $report->days_pending }} days
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('coordinator.monthly.report.show', $report->report_id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    View
                                                </a>
                                                @if($report->status === DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                                                    <form action="{{ route('coordinator.report.approve', $report->report_id) }}"
                                                          method="POST"
                                                          style="display: inline-block;"
                                                          onsubmit="return confirm('Are you sure you want to approve report {{ $report->report_id }}?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            Approve
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-warning btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#revertModal{{ $report->report_id }}">
                                                        Revert
                                                    </button>
                                                @endif
                                                <a href="{{ route('coordinator.monthly.report.downloadPdf', $report->report_id) }}"
                                                   class="btn btn-secondary btn-sm"
                                                   target="_blank">
                                                    Download PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Revert Modal -->
                                    @if($report->status === DPReport::STATUS_FORWARDED_TO_COORDINATOR)
                                    <div class="modal fade" id="revertModal{{ $report->report_id }}" tabindex="-1" aria-labelledby="revertModalLabel{{ $report->report_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="revertModalLabel{{ $report->report_id }}">Revert Report to Provincial</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('coordinator.report.revert', $report->report_id) }}">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Report ID:</strong> {{ $report->report_id }}</p>
                                                        <p><strong>Project:</strong> {{ $report->project_title }}</p>
                                                        <p><strong>Executor:</strong> {{ $report->user->name ?? 'N/A' }}</p>
                                                        <p><strong>Provincial:</strong> {{ $report->user->parent->name ?? 'N/A' }}</p>
                                                        <div class="mb-3">
                                                            <label for="revert_reason{{ $report->report_id }}" class="form-label">Reason for Revert *</label>
                                                            <textarea class="form-control auto-resize-textarea"
                                                                      id="revert_reason{{ $report->report_id }}"
                                                                      name="revert_reason"
                                                                      rows="3"
                                                                      required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning">Revert to Provincial</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="15" class="text-center py-4 text-muted">
                                            No reports found matching the filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if(isset($pagination) && $pagination['total'] > $pagination['per_page'])
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                Showing {{ $pagination['from'] }} to {{ $pagination['to'] }} of {{ $pagination['total'] }} reports
                            </small>
                        </div>
                        <div>
                            @if($pagination['current_page'] > 1)
                                <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}"
                                   class="btn btn-sm btn-secondary">Previous</a>
                            @endif

                            @for($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['last_page'], $pagination['current_page'] + 2); $i++)
                                @if($i == $pagination['current_page'])
                                    <span class="btn btn-sm btn-primary">{{ $i }}</span>
                                @else
                                    <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                                       class="btn btn-sm btn-outline-secondary">{{ $i }}</a>
                                @endif
                            @endfor

                            @if($pagination['current_page'] < $pagination['last_page'])
                                <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}"
                                   class="btn btn-sm btn-secondary">Next</a>
                            @endif
                        </div>
                    </div>
                    @elseif(isset($pagination))
                    <div class="mt-3">
                        <small class="text-muted">
                            Showing {{ $pagination['total'] }} report(s)
                        </small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Advanced Filters
    window.toggleAdvancedFilters = function() {
        const filters = document.getElementById('advancedFilters');
        filters.style.display = filters.style.display === 'none' ? 'block' : 'none';
    };

    // Toggle Bulk Actions
    window.toggleBulkActions = function() {
        const bulkActions = document.getElementById('bulkActions');
        const isVisible = bulkActions.style.display !== 'none';
        bulkActions.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            // Show checkboxes for pending reports
            document.querySelectorAll('.report-checkbox').forEach(cb => {
                cb.style.display = 'block';
            });
        }
    };

    // Select All Checkbox
    const selectAll = document.getElementById('selectAllReports');
    const checkboxes = document.querySelectorAll('.report-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButtons();
        });
    }

    // Individual Checkbox Change
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActionButtons();

            // Update select all checkbox
            if (selectAll) {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                selectAll.checked = allChecked;
            }
        });
    });

    // Update Bulk Action Buttons
    function updateBulkActionButtons() {
        const selected = document.querySelectorAll('.report-checkbox:checked');
        const count = selected.length;

        document.getElementById('selectedCount').textContent = count;

        const approveBtn = document.getElementById('bulkApproveBtn');
        const revertBtn = document.getElementById('bulkRevertBtn');

        if (approveBtn) approveBtn.disabled = count === 0;
        if (revertBtn) revertBtn.disabled = count === 0;
    }

    // Clear Selection
    window.clearSelection = function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        if (selectAll) selectAll.checked = false;
        updateBulkActionButtons();
    };

    // Bulk Approve
    window.bulkApprove = function() {
        const selected = Array.from(document.querySelectorAll('.report-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Please select at least one report to approve.');
            return;
        }

        if (confirm(`Are you sure you want to approve ${selected.length} report(s)?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("coordinator.report.bulk-action") }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'bulk_approve';
            form.appendChild(actionInput);

            selected.forEach(reportId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'report_ids[]';
                input.value = reportId;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    };

    // Bulk Revert
    window.bulkRevert = function() {
        const selected = Array.from(document.querySelectorAll('.report-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Please select at least one report to revert.');
            return;
        }

        const reason = prompt(`Please provide a reason for reverting ${selected.length} report(s):`);
        if (reason && reason.trim()) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("coordinator.report.bulk-action") }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'bulk_revert';
            form.appendChild(actionInput);

            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'revert_reason';
            reasonInput.value = reason;
            form.appendChild(reasonInput);

            selected.forEach(reportId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'report_ids[]';
                input.value = reportId;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    };
});
</script>
@endpush

@endsection
