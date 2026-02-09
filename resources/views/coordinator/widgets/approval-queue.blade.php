{{-- Approval Queue Widget --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Approval Queue
            @if(isset($pendingApprovalsData['total_pending']) && $pendingApprovalsData['total_pending'] > 0)
                <span class="badge badge-danger ms-2">{{ $pendingApprovalsData['total_pending'] }}</span>
            @endif
        </h5>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-success" id="bulkApproveBtn" disabled>Bulk Approve</button>
            <a href="{{ route('coordinator.report.list', ['status' => 'forwarded_to_coordinator']) }}" class="btn btn-sm btn-primary">View All</a>
        </div>
    </div>
    <div class="card-body">
        @if(isset($pendingApprovalsData['total_pending']) && $pendingApprovalsData['total_pending'] > 0)
            {{-- Filters --}}
            <div class="row mb-3">
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="urgencyFilter">
                        <option value="">All Urgency Levels</option>
                        <option value="urgent">Urgent (>7 days)</option>
                        <option value="normal">Normal (3-7 days)</option>
                        <option value="low">Low (<3 days)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="provinceFilter">
                        <option value="">All Provinces</option>
                        @if(isset($pendingApprovalsData['by_province']))
                            @foreach($pendingApprovalsData['by_province']->keys() as $province)
                                <option value="{{ $province }}">{{ $province }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm" id="searchFilter" placeholder="Search by Report ID...">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-sm btn-secondary w-100" onclick="clearFilters()">Clear Filters</button>
                </div>
            </div>

            {{-- Approval Queue Table --}}
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover table-sm" id="approvalQueueTable">
                    <thead class="thead-light sticky-top">
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" title="Select All">
                            </th>
                            <th>Report ID</th>
                            <th>Project</th>
                            <th>Submitter</th>
                            <th>Province</th>
                            <th>Provincial</th>
                            <th>Center</th>
                            <th>Days Pending</th>
                            <th>Urgency</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingApprovalsData['pending_reports'] ?? [] as $report)
                            <tr class="approval-row"
                                data-urgency="{{ $report->urgency }}"
                                data-province="{{ $report->user->province ?? '' }}"
                                data-report-id="{{ $report->report_id }}">
                                <td>
                                    <input type="checkbox" class="report-checkbox" value="{{ $report->report_id }}" data-report-id="{{ $report->report_id }}">
                                </td>
                                <td>
                                    <a href="{{ route('coordinator.monthly.report.show', $report->report_id) }}" class="text-primary font-weight-bold">
                                        {{ $report->report_id }}
                                    </a>
                                </td>
                                <td>
                                    <small>{{ Str::limit($report->project_title ?? $report->project_id, 30) }}</small>
                                </td>
                                <td>
                                    <small>{{ $report->user->name ?? 'N/A' }}</small>
                                    <br>
                                    <span class="badge badge-secondary badge-sm">{{ $report->user->role ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $report->user->province ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <small>{{ $report->provincial->name ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <small>{{ $report->user->center ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $report->urgency === 'urgent' ? 'danger' : ($report->urgency === 'normal' ? 'warning' : 'success') }}">
                                        {{ $report->days_pending }} days
                                    </span>
                                </td>
                                <td>
                                    @if($report->urgency === 'urgent')
                                        <span class="badge badge-danger">⚠ Urgent</span>
                                    @elseif($report->urgency === 'normal')
                                        <span class="badge badge-warning">⏱ Normal</span>
                                    @else
                                        <span class="badge badge-success">✓ Low</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="{{ route('coordinator.monthly.report.show', $report->report_id) }}" class="btn btn-sm btn-info">
                                            View
                                        </a>
                                        <form action="{{ route('coordinator.report.approve', $report->report_id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success quick-approve-single"
                                                    data-report-id="{{ $report->report_id }}"
                                                    onclick="return confirm('Are you sure you want to approve report {{ $report->report_id }}?')">
                                                Approve
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-warning quick-revert-single"
                                                data-report-id="{{ $report->report_id }}"
                                                onclick="showRevertModal('{{ $report->report_id }}')">
                                            Revert
                                        </button>
                                        <a href="{{ route('coordinator.monthly.report.downloadPdf', $report->report_id) }}" class="btn btn-sm btn-secondary">
                                            Download PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No pending reports in approval queue</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <p class="text-muted">No reports pending approval! All caught up.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select All checkbox
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.report-checkbox');
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkApproveButton();
        });
    }

    // Individual checkbox change
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkApproveButton();
            // Update select all checkbox
            if (selectAll) {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                selectAll.checked = allChecked;
            }
        });
    });

    // Update bulk approve button state
    function updateBulkApproveButton() {
        const selected = document.querySelectorAll('.report-checkbox:checked');
        if (bulkApproveBtn) {
            bulkApproveBtn.disabled = selected.length === 0;
            if (selected.length > 0) {
                bulkApproveBtn.textContent = `Bulk Approve (${selected.length})`;
            } else {
                bulkApproveBtn.textContent = 'Bulk Approve';
            }
        }
    }

    // Bulk Approve
    if (bulkApproveBtn) {
        bulkApproveBtn.addEventListener('click', function() {
            const selected = Array.from(document.querySelectorAll('.report-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Please select at least one report to approve.');
                return;
            }

            if (confirm(`Are you sure you want to approve ${selected.length} report(s)?`)) {
                // Create a form and submit for bulk approve
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ route('coordinator.report.list') }}`;

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
                    const reportInput = document.createElement('input');
                    reportInput.type = 'hidden';
                    reportInput.name = 'report_ids[]';
                    reportInput.value = reportId;
                    form.appendChild(reportInput);
                });

                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Quick Revert Single - handled by onclick attribute
    function showRevertModal(reportId) {
        const reason = prompt(`Please provide a reason for reverting report ${reportId}:`);
        if (reason && reason.trim()) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ url('/coordinator/report') }}/${reportId}/revert`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'revert_reason';
            reasonInput.value = reason;
            form.appendChild(reasonInput);

            document.body.appendChild(form);
            form.submit();
        }
    }

    window.showRevertModal = showRevertModal;

    // Filters
    const urgencyFilter = document.getElementById('urgencyFilter');
    const provinceFilter = document.getElementById('provinceFilter');
    const searchFilter = document.getElementById('searchFilter');

    function applyFilters() {
        const urgency = urgencyFilter ? urgencyFilter.value : '';
        const province = provinceFilter ? provinceFilter.value : '';
        const search = searchFilter ? searchFilter.value.toLowerCase() : '';

        document.querySelectorAll('.approval-row').forEach(row => {
            let show = true;

            if (urgency && row.getAttribute('data-urgency') !== urgency) {
                show = false;
            }

            if (province && row.getAttribute('data-province') !== province) {
                show = false;
            }

            if (search) {
                const reportId = row.getAttribute('data-report-id').toLowerCase();
                if (!reportId.includes(search)) {
                    show = false;
                }
            }

            row.style.display = show ? '' : 'none';
        });
    }

    if (urgencyFilter) urgencyFilter.addEventListener('change', applyFilters);
    if (provinceFilter) provinceFilter.addEventListener('change', applyFilters);
    if (searchFilter) {
        searchFilter.addEventListener('keyup', applyFilters);
        searchFilter.addEventListener('input', applyFilters);
    }

    function clearFilters() {
        if (urgencyFilter) urgencyFilter.value = '';
        if (provinceFilter) provinceFilter.value = '';
        if (searchFilter) searchFilter.value = '';
        document.querySelectorAll('.approval-row').forEach(row => {
            row.style.display = '';
        });
    }

    window.clearFilters = clearFilters;
});
</script>
@endpush
