@php
    use App\Models\Reports\Monthly\DPReport;
    use App\Models\OldProjects\Project;
    use App\Constants\ProjectStatus;

    $totalQueueCount = (isset($approvalQueueProjects) ? $approvalQueueProjects->count() : 0) + (isset($approvalQueueReports) ? $approvalQueueReports->count() : 0);
@endphp
{{-- Approval Queue Widget (Projects & Reports) --}}
<div class="card mb-4 widget-card" data-widget-id="approval-queue">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Approval Queue
            @if($totalQueueCount > 0)
                <span class="badge bg-danger ms-2">{{ $totalQueueCount }}</span>
                <small class="text-muted ms-2">({{ isset($approvalQueueProjects) ? $approvalQueueProjects->count() : 0 }} Projects, {{ isset($approvalQueueReports) ? $approvalQueueReports->count() : 0 }} Reports)</small>
            @endif
        </h5>
        <div>
            @if(isset($approvalQueueReports) && $approvalQueueReports->count() > 0)
                <button type="button" class="btn btn-sm btn-outline-success me-2" id="bulkApproveBtn" disabled>Bulk Approve Reports</button>
            @endif
            <a href="{{ route('provincial.projects.list') }}" class="btn btn-sm btn-outline-primary me-2">View Projects</a>
            <a href="{{ route('provincial.report.pending') }}" class="btn btn-sm btn-outline-primary me-2">View Reports</a>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="approval-queue" title="Minimize">−</button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if($totalQueueCount == 0)
            <div class="text-center py-4">
                <p class="text-muted">Approval queue is empty</p>
            </div>
        @else
            {{-- Tabs for Projects and Reports --}}
            <ul class="nav nav-tabs mb-3" id="approvalQueueTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active"
                            id="queue-projects-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#queue-projects"
                            type="button"
                            role="tab">
Projects
                        @if(isset($approvalQueueProjects) && $approvalQueueProjects->count() > 0)
                            <span class="badge bg-danger ms-2">{{ $approvalQueueProjects->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                            id="queue-reports-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#queue-reports"
                            type="button"
                            role="tab">
Reports
                        @if(isset($approvalQueueReports) && $approvalQueueReports->count() > 0)
                            <span class="badge bg-danger ms-2">{{ $approvalQueueReports->count() }}</span>
                        @endif
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="approvalQueueTabContent">
                {{-- Projects Tab --}}
                <div class="tab-pane fade show active" id="queue-projects" role="tabpanel">
                    @if(!isset($approvalQueueProjects) || $approvalQueueProjects->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted">No pending projects in queue</p>
                        </div>
                    @else
                        {{-- Filters for Projects --}}
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="queueProjectUrgencyFilter" class="form-label small text-muted">Urgency</label>
                                <select class="form-select form-select-sm" id="queueProjectUrgencyFilter" onchange="window.filterProjectQueue && window.filterProjectQueue();">
                                    <option value="">All Urgency</option>
                                    <option value="urgent">Urgent (>7 days)</option>
                                    <option value="normal">Normal (3-7 days)</option>
                                    <option value="low">Low (<3 days)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="queueProjectCenterFilter" class="form-label small text-muted">Center</label>
                                <select class="form-select form-select-sm" id="queueProjectCenterFilter" name="queueProjectCenterFilter" onchange="window.filterProjectQueue && window.filterProjectQueue();">
                                    <option value="">All Centers</option>
                                    @if(isset($approvalQueueProjects) && $approvalQueueProjects->count() > 0)
                                        @php
                                            $queueCenters = $approvalQueueProjects->map(function($project) {
                                                return trim($project->user->center ?? '');
                                            })->filter(function($center) {
                                                return !empty($center);
                                            })->unique()->sort()->values();
                                        @endphp
                                        @foreach($queueCenters as $center)
                                            <option value="{{ trim($center) }}">{{ $center }}</option>
                                        @endforeach
                                    @else
                                        @foreach($allCenters ?? $centers ?? [] as $center)
                                            <option value="{{ trim($center) }}">{{ $center }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1" style="opacity: 0; height: 1.5em; display: block;">Action</label>
                                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="clearProjectFilters" style="height: calc(1.5em + 0.5rem + 2px);">
                                    Clear Filters
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="approvalQueueProjectsTable">
                                <thead>
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Title</th>
                                        <th>Team Member</th>
                                        <th>Center</th>
                                        <th>Days Pending</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvalQueueProjects->take(15) as $project)
                                        @php
                                            $daysPending = $project->days_pending ?? $project->created_at->diffInDays(now());
                                            $urgency = $project->urgency ?? ($daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low'));
                                            $urgencyClass = $urgency === 'urgent' ? 'danger' : ($urgency === 'normal' ? 'warning' : 'success');
                                            $urgencyBadge = $urgency === 'urgent' ? 'Urgent' : ($urgency === 'normal' ? 'Normal' : 'Low');
                                            $statusLabel = $project->status === ProjectStatus::SUBMITTED_TO_PROVINCIAL
                                                ? 'Submitted to Provincial'
                                                : 'Reverted by Coordinator';
                                            $statusBadgeClass = $project->status === ProjectStatus::SUBMITTED_TO_PROVINCIAL
                                                ? 'bg-primary'
                                                : 'bg-warning';
                                        @endphp
                                        <tr class="align-middle queue-project-row"
                                            data-urgency="{{ $urgency }}"
                                            data-center="{{ trim($project->user->center ?? '') }}"
                                            data-member="{{ $project->user_id }}"
                                            data-days="{{ $daysPending }}">
                                            <td>
                                                <a href="{{ route('provincial.projects.show', $project->project_id) }}"
                                                   class="text-decoration-none fw-bold">
                                                    {{ $project->project_id }}
                                                </a>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($project->project_title, 40) }}</small>
                                                @if($project->project_type)
                                                    <br><span class="badge bg-secondary badge-sm">{{ $project->project_type }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $project->user->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ ucfirst($project->user->role) }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $project->user->center ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $daysPending }} days</span>
                                                <br>
                                                <small class="text-muted">{{ $project->created_at->format('M d, Y') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $urgencyBadge }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <a href="{{ route('provincial.projects.show', $project->project_id) }}"
                                                       class="btn btn-sm btn-primary">
                                                        View
                                                    </a>
                                                    <form method="POST" action="{{ route('projects.forwardToCoordinator', $project->project_id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Forward this project to coordinator?');">
                                                            Forward
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning revert-project-queue-btn"
                                                            data-project-id="{{ $project->project_id }}"
                                                            data-project-title="{{ $project->project_title }}">
                                                        Revert
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($approvalQueueProjects->count() > 15)
                            <div class="text-center mt-3">
                                <a href="{{ route('provincial.projects.list') }}" class="btn btn-sm btn-outline-primary">
                                    View All {{ $approvalQueueProjects->count() }} Pending Projects
                                </a>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Reports Tab --}}
                <div class="tab-pane fade" id="queue-reports" role="tabpanel">
                    @if(!isset($approvalQueueReports) || $approvalQueueReports->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted">No pending reports in queue</p>
                        </div>
                    @else
                        {{-- Filters for Reports --}}
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="queueUrgencyFilter" class="form-label small text-muted">Urgency</label>
                                <select class="form-select form-select-sm" id="queueUrgencyFilter" onchange="window.filterQueue && window.filterQueue();">
                                    <option value="">All Urgency</option>
                                    <option value="urgent">Urgent (>7 days)</option>
                                    <option value="normal">Normal (3-7 days)</option>
                                    <option value="low">Low (<3 days)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="queueCenterFilter" class="form-label small text-muted">Center</label>
                                <select class="form-select form-select-sm" id="queueCenterFilter" onchange="window.filterQueue && window.filterQueue();">
                                    <option value="">All Centers</option>
                                    @if(isset($approvalQueueReports) && $approvalQueueReports->count() > 0)
                                        @php
                                            // Get unique centers from approval queue reports only
                                            $queueReportCenters = $approvalQueueReports->map(function($report) {
                                                return trim($report->user->center ?? '');
                                            })->filter(function($center) {
                                                return !empty($center);
                                            })->unique()->sort()->values();
                                        @endphp
                                        @foreach($queueReportCenters as $center)
                                            <option value="{{ trim($center) }}">{{ $center }}</option>
                                        @endforeach
                                    @else
                                        @foreach($allCenters ?? $centers ?? [] as $center)
                                            <option value="{{ trim($center) }}">{{ $center }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="queueMemberFilter" class="form-label small text-muted">Team Member</label>
                                <select class="form-select form-select-sm" id="queueMemberFilter" onchange="window.filterQueue && window.filterQueue();">
                                    <option value="">All Team Members</option>
                                    @if(isset($teamMembersForQueue) && is_iterable($teamMembersForQueue))
                                        @foreach($teamMembersForQueue as $member)
                                            @if(isset($member->id) && isset($member->name))
                                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1" style="opacity: 0; height: 1.5em; display: block;">Action</label>
                                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="clearQueueFilters" style="height: calc(1.5em + 0.5rem + 2px);">
                                    Clear Filters
                                </button>
                            </div>
                        </div>

                        {{-- Reports Table with Bulk Actions --}}
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="approvalQueueTable">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAllQueue" class="form-check-input">
                                        </th>
                                        <th>Report ID</th>
                                        <th>Project</th>
                                        <th>Team Member</th>
                                        <th>Center</th>
                                        <th>Days Pending</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvalQueueReports as $report)
                                        @php
                                            $daysPending = $report->days_pending ?? $report->created_at->diffInDays(now());
                                            $urgency = $report->urgency ?? ($daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low'));
                                            $urgencyClass = $urgency === 'urgent' ? 'danger' : ($urgency === 'normal' ? 'warning' : 'success');
                                            $urgencyBadge = $urgency === 'urgent' ? 'Urgent' : ($urgency === 'normal' ? 'Normal' : 'Low');
                                            $statusLabel = $report->status === DPReport::STATUS_SUBMITTED_TO_PROVINCIAL
                                                ? 'Submitted to Provincial'
                                                : 'Reverted by Coordinator';
                                            $statusBadgeClass = $report->status === DPReport::STATUS_SUBMITTED_TO_PROVINCIAL
                                                ? 'bg-primary'
                                                : 'bg-warning';
                                        @endphp
                                        <tr class="align-middle queue-row"
                                            data-urgency="{{ $urgency }}"
                                            data-center="{{ trim($report->user->center ?? '') }}"
                                            data-member="{{ $report->user_id }}"
                                            data-days="{{ $daysPending }}">
                                            <td>
                                                <input type="checkbox"
                                                       class="form-check-input queue-checkbox"
                                                       value="{{ $report->report_id }}"
                                                       data-report-id="{{ $report->report_id }}">
                                            </td>
                                            <td>
                                                <a href="{{ route('provincial.monthly.report.show', $report->report_id) }}"
                                                   class="text-decoration-none fw-bold">
                                                    {{ $report->report_id }}
                                                </a>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($report->project_title, 40) }}</small>
                                                @if($report->project_type ?? null)
                                                    <br><span class="badge bg-secondary badge-sm">{{ $report->project_type }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $report->user->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ ucfirst($report->user->role) }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $report->user->center ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $daysPending }} days</span>
                                                <br>
                                                <small class="text-muted">{{ $report->created_at->format('M d, Y') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $urgencyBadge }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                                @if($report->status === DPReport::STATUS_REVERTED_BY_COORDINATOR && ($report->revert_reason ?? null))
                                                    <br>
                                                    <button type="button"
                                                            class="btn btn-link btn-sm p-0 mt-1"
                                                            data-bs-toggle="tooltip"
                                                            title="{{ $report->revert_reason }}">
                                                        <small>View Reason</small>
                                                    </button>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <a href="{{ route('provincial.monthly.report.show', $report->report_id) }}"
                                                       class="btn btn-sm btn-primary">
                                                        View
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-sm btn-success quick-approve-btn"
                                                            data-report-id="{{ $report->report_id }}"
                                                            data-report-title="{{ $report->project_title }}">
                                                        Forward
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning quick-revert-btn"
                                                            data-report-id="{{ $report->report_id }}"
                                                            data-report-title="{{ $report->project_title }}">
                                                        Revert
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($approvalQueueReports->count() > 15)
                            <div class="text-center mt-3">
                                <a href="{{ route('provincial.report.pending') }}" class="btn btn-sm btn-outline-primary">
                                    View All {{ $approvalQueueReports->count() }} Pending Reports
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Bulk Approve Confirmation Modal --}}
<div class="modal fade" id="bulkApproveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Forward to Coordinator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkApproveForm" method="POST" action="{{ route('provincial.report.bulk-forward') }}">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to forward <strong><span id="bulkApproveCount">0</span></strong> selected report(s) to the coordinator?</p>
                    <div id="bulkApproveList" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Forward Selected Reports</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Define filter functions IMMEDIATELY (before any DOM operations) to ensure they're available for inline handlers
// Projects Queue filters - Global function for filtering
window.filterProjectQueue = function() {
    const projectUrgencyFilter = document.getElementById('queueProjectUrgencyFilter');
    const projectCenterFilter = document.getElementById('queueProjectCenterFilter');

    if (!projectUrgencyFilter && !projectCenterFilter) {
        return; // Elements don't exist yet
    }

    const urgency = (projectUrgencyFilter?.value || '').trim();
    const center = (projectCenterFilter?.value || '').trim();

    let visibleCount = 0;
    const allRows = document.querySelectorAll('.queue-project-row');

    if (allRows.length === 0) {
        return; // No rows to filter
    }

    allRows.forEach((row) => {
        const rowUrgency = (row.dataset.urgency || '').trim();
        const rowCenter = (row.dataset.center || '').trim();

        const urgencyMatch = !urgency || rowUrgency === urgency;
        // Case-insensitive and trimmed center matching - handle empty strings
        const centerMatch = !center || (rowCenter && center && rowCenter.toLowerCase().trim() === center.toLowerCase().trim());

        const show = urgencyMatch && centerMatch;

        if (show) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide no results message
    const projectsTab = document.getElementById('queue-projects');
    if (projectsTab) {
        let noResultsMsg = projectsTab.querySelector('.no-results-message');

        if (visibleCount === 0 && allRows.length > 0 && (urgency || center)) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'alert alert-info text-center mt-3 no-results-message';
                noResultsMsg.innerHTML = 'No projects match the selected filters.';
                const tableContainer = projectsTab.querySelector('.table-responsive');
                if (tableContainer && tableContainer.parentNode) {
                    tableContainer.parentNode.insertBefore(noResultsMsg, tableContainer.nextSibling);
                    if (typeof feather !== 'undefined') feather.replace();
                }
            } else {
                noResultsMsg.style.display = 'block';
            }
        } else if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    }
};

// Reports Queue filters - Global function for filtering
window.filterQueue = function() {
    const urgencyFilter = document.getElementById('queueUrgencyFilter');
    const centerFilter = document.getElementById('queueCenterFilter');
    const memberFilter = document.getElementById('queueMemberFilter');

    const urgency = (urgencyFilter?.value || '').trim();
    const center = (centerFilter?.value || '').trim();
    const member = (memberFilter?.value || '').trim();

    let visibleCount = 0;
    const allRows = document.querySelectorAll('.queue-row');

    if (allRows.length === 0) {
        return; // No rows to filter
    }

    allRows.forEach((row) => {
        const rowUrgency = (row.dataset.urgency || '').trim();
        const rowCenter = (row.dataset.center || '').trim();
        const rowMember = (row.dataset.member || '').trim();

        const urgencyMatch = !urgency || rowUrgency === urgency;
        const centerMatch = !center || (rowCenter && center && rowCenter.toLowerCase().trim() === center.toLowerCase().trim());
        const memberMatch = !member || rowMember === member;

        const show = urgencyMatch && centerMatch && memberMatch;

        if (show) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide no results message
    const reportsTab = document.getElementById('queue-reports');
    if (reportsTab) {
        let noResultsMsg = reportsTab.querySelector('.no-results-message');

        if (visibleCount === 0 && allRows.length > 0 && (urgency || center || member)) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'alert alert-info text-center mt-3 no-results-message';
                noResultsMsg.innerHTML = 'No reports match the selected filters.';
                const tableContainer = reportsTab.querySelector('.table-responsive');
                if (tableContainer && tableContainer.parentNode) {
                    tableContainer.parentNode.insertBefore(noResultsMsg, tableContainer.nextSibling);
                    if (typeof feather !== 'undefined') feather.replace();
                }
            } else {
                noResultsMsg.style.display = 'block';
            }
        } else if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize tabs
    const triggerTabList = document.querySelectorAll('#approvalQueueTabs button[data-bs-toggle="tab"]');
    triggerTabList.forEach(triggerEl => {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener('click', event => {
            event.preventDefault();
            tabTrigger.show();
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Select all checkbox
    const selectAllCheckbox = document.getElementById('selectAllQueue');
    const queueCheckboxes = document.querySelectorAll('.queue-checkbox');
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            queueCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkApproveButton();
        });
    }

    // Individual checkbox change
    queueCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = Array.from(queueCheckboxes).every(cb => cb.checked);
            }
            updateBulkApproveButton();
        });
    });

    function updateBulkApproveButton() {
        const selectedCount = document.querySelectorAll('.queue-checkbox:checked').length;
        if (bulkApproveBtn) {
            bulkApproveBtn.disabled = selectedCount === 0;
            bulkApproveBtn.textContent = `Bulk Approve (${selectedCount})`;
        }
    }

    // Bulk approve button
    if (bulkApproveBtn) {
        bulkApproveBtn.addEventListener('click', function() {
            const selectedCheckboxes = document.querySelectorAll('.queue-checkbox:checked');
            const reportIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.reportId);

            document.getElementById('bulkApproveCount').textContent = reportIds.length;

            // Update form with selected report IDs - create multiple hidden inputs
            // Remove any existing report_ids inputs
            document.querySelectorAll('input[name="report_ids[]"]').forEach(input => input.remove());

            // Create individual hidden inputs for each report ID
            reportIds.forEach(reportId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'report_ids[]';
                input.value = reportId;
                document.getElementById('bulkApproveForm').appendChild(input);
            });

            // Show selected reports
            const listDiv = document.getElementById('bulkApproveList');
            listDiv.innerHTML = '<ul class="list-group">' +
                Array.from(selectedCheckboxes).map(cb => {
                    const row = cb.closest('tr');
                    const reportId = row.querySelector('td:nth-child(2)').textContent.trim();
                    const projectTitle = row.querySelector('td:nth-child(3)').textContent.trim();
                    return `<li class="list-group-item">${reportId} - ${projectTitle}</li>`;
                }).join('') +
                '</ul>';

            const modal = new bootstrap.Modal(document.getElementById('bulkApproveModal'));
            modal.show();
        });
    }

    // Initialize reports queue filter event listeners (function already defined above)
    // Note: Inline onchange handlers should be added to HTML for reports filter too
    function initReportsQueueFilters() {
        // Verify filter function exists
        if (typeof window.filterQueue !== 'function') {
            console.error('window.filterQueue is not defined');
            return;
        }

        // Add event listeners as backup (inline handlers are primary)
        const urgencyFilter = document.getElementById('queueUrgencyFilter');
        const centerFilter = document.getElementById('queueCenterFilter');
        const memberFilter = document.getElementById('queueMemberFilter');
        const clearFiltersBtn = document.getElementById('clearQueueFilters');

        if (urgencyFilter) {
            urgencyFilter.addEventListener('change', function() {
                window.filterQueue();
            });
        }

        if (centerFilter) {
            centerFilter.addEventListener('change', function() {
                window.filterQueue();
            });
            centerFilter.addEventListener('input', function() {
                window.filterQueue();
            });
        }

        if (memberFilter) {
            memberFilter.addEventListener('change', function() {
                window.filterQueue();
            });
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (urgencyFilter) urgencyFilter.value = '';
                if (centerFilter) centerFilter.value = '';
                if (memberFilter) memberFilter.value = '';
                window.filterQueue();
            });
        }
    }

    // Initialize immediately (functions are already defined above)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initReportsQueueFilters();
        });
    } else {
        // DOM already loaded, initialize immediately
        initReportsQueueFilters();
    }

    // Also initialize when Reports tab is shown (Bootstrap tab event)
    const reportsTabBtn = document.getElementById('queue-reports-tab');
    if (reportsTabBtn) {
        reportsTabBtn.addEventListener('shown.bs.tab', function() {
            setTimeout(initReportsQueueFilters, 50);
        });
    }

    // Initialize project queue filter event listeners (function already defined above)
    // Note: Inline onchange handlers are already in the HTML, so we just ensure they work
    // The inline handlers will call window.filterProjectQueue() directly
    function initProjectQueueFilters() {
        // Verify filter function exists
        if (typeof window.filterProjectQueue !== 'function') {
            console.error('window.filterProjectQueue is not defined');
            return;
        }

        // Inline handlers should work, but addEventListener as backup (doesn't override inline)
        const projectUrgencyFilter = document.getElementById('queueProjectUrgencyFilter');
        const projectCenterFilter = document.getElementById('queueProjectCenterFilter');
        const clearProjectFiltersBtn = document.getElementById('clearProjectFilters');

        if (projectUrgencyFilter) {
            // Use addEventListener to ADD to inline handler, not replace it
            projectUrgencyFilter.addEventListener('change', function() {
                window.filterProjectQueue();
            });
        }

        if (projectCenterFilter) {
            // Use addEventListener to ADD to inline handler, not replace it
            projectCenterFilter.addEventListener('change', function() {
                window.filterProjectQueue();
            });
            projectCenterFilter.addEventListener('input', function() {
                window.filterProjectQueue();
            });
        }

        if (clearProjectFiltersBtn) {
            clearProjectFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (projectUrgencyFilter) projectUrgencyFilter.value = '';
                if (projectCenterFilter) projectCenterFilter.value = '';
                window.filterProjectQueue();
            });
        }
    }

    // Initialize immediately (functions are already defined above)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initProjectQueueFilters();
        });
    } else {
        // DOM already loaded, initialize immediately
        initProjectQueueFilters();
    }

    // Also initialize when Projects tab is shown (Bootstrap tab event)
    const projectsTabBtn = document.getElementById('queue-projects-tab');
    if (projectsTabBtn) {
        projectsTabBtn.addEventListener('shown.bs.tab', function() {
            setTimeout(initProjectQueueFilters, 50);
        });
    }

    // Project revert button handler (with modal for reason)
    document.querySelectorAll('.revert-project-queue-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const projectId = this.dataset.projectId;
            const projectTitle = this.dataset.projectTitle;

            // Use the revert project modal from pending-approvals widget if it exists
            const revertProjectModal = document.getElementById('revertProjectModal');
            if (revertProjectModal) {
                document.getElementById('revertProjectId').textContent = projectId;
                document.getElementById('revertProjectTitleText').textContent = projectTitle;
                document.getElementById('revertProjectForm').action = '{{ route("projects.revertToExecutor", ":id") }}'.replace(':id', projectId);
                document.getElementById('revert_project_reason').value = '';

                const modal = new bootstrap.Modal(revertProjectModal);
                modal.show();
            } else {
                // Fallback: direct form submission with confirm
                if (confirm(`Revert project ${projectId} to executor?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("projects.revertToExecutor", ":id") }}'.replace(':id', projectId);
                    form.innerHTML = '@csrf';
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
    });

    // Quick approve/revert handlers (same as pending-approvals widget)
    document.querySelectorAll('.quick-approve-btn').forEach(button => {
        button.addEventListener('click', function() {
            const reportId = this.dataset.reportId;
            const reportTitle = this.dataset.reportTitle;

            // Use the approve modal from pending-approvals widget if it exists
            const approveModal = document.getElementById('approveModal');
            if (approveModal) {
                document.getElementById('approveReportId').textContent = reportId;
                document.getElementById('approveProjectTitle').textContent = reportTitle;
                document.getElementById('approveForm').action = '{{ route("provincial.report.forward", ":id") }}'.replace(':id', reportId);

                const modal = new bootstrap.Modal(approveModal);
                modal.show();
            } else {
                // Fallback: direct form submission
                if (confirm(`Forward report ${reportId} to coordinator?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("provincial.report.forward", ":id") }}'.replace(':id', reportId);
                    form.innerHTML = '@csrf';
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
    });

    document.querySelectorAll('.quick-revert-btn').forEach(button => {
        button.addEventListener('click', function() {
            const reportId = this.dataset.reportId;
            const reportTitle = this.dataset.reportTitle;

            // Use the revert modal from pending-approvals widget if it exists
            const revertModal = document.getElementById('revertModal');
            if (revertModal) {
                document.getElementById('revertReportId').textContent = reportId;
                document.getElementById('revertProjectTitle').textContent = reportTitle;
                document.getElementById('revertForm').action = '{{ route("provincial.report.revert", ":id") }}'.replace(':id', reportId);
                document.getElementById('revert_reason').value = '';

                const modal = new bootstrap.Modal(revertModal);
                modal.show();
            }
        });
    });
});
</script>
@endpush
