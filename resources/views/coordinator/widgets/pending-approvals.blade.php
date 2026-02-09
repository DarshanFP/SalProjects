@php
    use App\Models\Reports\Monthly\DPReport;
    use App\Models\OldProjects\Project;
    use App\Constants\ProjectStatus;

    $pendingReports = $pendingApprovalsData['pending_reports'] ?? collect();
    $pendingProjects = $pendingApprovalsData['pending_projects'] ?? collect();
    $totalPendingCount = $pendingApprovalsData['total_pending'] ?? 0;
    $pendingReportsCount = $pendingApprovalsData['pending_reports_count'] ?? 0;
    $pendingProjectsCount = $pendingApprovalsData['pending_projects_count'] ?? 0;
    $urgentCount = $pendingApprovalsData['urgent_count'] ?? 0;
    $normalCount = $pendingApprovalsData['normal_count'] ?? 0;
    $lowCount = $pendingApprovalsData['low_count'] ?? 0;
    $urgentProjectsCount = $pendingApprovalsData['urgent_projects_count'] ?? 0;
    $normalProjectsCount = $pendingApprovalsData['normal_projects_count'] ?? 0;
    $totalUrgentCount = $pendingApprovalsData['total_urgent_count'] ?? 0;
    $totalNormalCount = $pendingApprovalsData['total_normal_count'] ?? 0;
@endphp

{{-- Pending Approvals Widget (Projects & Reports) --}}
<div class="card mb-4 widget-card" data-widget-id="pending-approvals">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Pending Approvals
            @if($totalPendingCount > 0)
                <span class="badge bg-danger ms-2">{{ $totalPendingCount }}</span>
            @endif
        </h5>
        <div>
            <a href="{{ route('coordinator.report.list', ['status' => 'forwarded_to_coordinator']) }}" class="btn btn-sm btn-outline-primary me-2">View All Reports</a>
            <a href="{{ route('coordinator.projects.list', ['status' => 'forwarded_to_coordinator']) }}" class="btn btn-sm btn-outline-primary me-2">View All Projects</a>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="pending-approvals" title="Minimize">−</button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if($totalPendingCount == 0)
            <div class="text-center py-4">
                <p class="text-muted">No pending approvals</p>
            </div>
        @else
            {{-- Summary Cards with Fixed Height --}}
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Total Pending</small>
                            <h4 class="mb-0">{{ $totalPendingCount }}</h4>
                            <small class="d-block mt-1">{{ $pendingProjectsCount }} Projects, {{ $pendingReportsCount }} Reports</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Urgent (>7 days)</small>
                            <h4 class="mb-0">{{ $totalUrgentCount }}</h4>
                            <small class="d-block mt-1">{{ $urgentProjectsCount }} Projects, {{ $urgentCount }} Reports</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Normal (3-7 days)</small>
                            <h4 class="mb-0">{{ $totalNormalCount }}</h4>
                            <small class="d-block mt-1">{{ $normalProjectsCount }} Projects, {{ $normalCount }} Reports</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Recent (<3 days)</small>
                            <h4 class="mb-0">{{ $totalPendingCount - ($totalUrgentCount + $totalNormalCount) }}</h4>
                            <small class="d-block mt-1">Recently submitted</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs for Projects and Reports --}}
            <ul class="nav nav-tabs mb-3" id="pendingApprovalsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active"
                            id="projects-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#projects-pending"
                            type="button"
                            role="tab">
Projects
                        @if($pendingProjectsCount > 0)
                            <span class="badge bg-danger ms-2">{{ $pendingProjectsCount }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                            id="reports-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#reports-pending"
                            type="button"
                            role="tab">
Reports
                        @if($pendingReportsCount > 0)
                            <span class="badge bg-danger ms-2">{{ $pendingReportsCount }}</span>
                        @endif
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="pendingApprovalsTabContent">
                {{-- Projects Tab --}}
                <div class="tab-pane fade show active" id="projects-pending" role="tabpanel">
                    @if($pendingProjects->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted">No pending projects</p>
                        </div>
                    @else
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-sm table-hover pending-approvals-table">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th style="width: 120px;">Project ID</th>
                                        <th style="width: 200px; min-width: 150px; max-width: 250px;">Title</th>
                                        <th style="width: 140px;">Executor/Applicant</th>
                                        <th style="width: 120px;">Province</th>
                                        <th style="width: 140px;">Provincial</th>
                                        <th style="width: 120px;">Days Pending</th>
                                        <th style="width: 100px;">Priority</th>
                                        <th style="width: auto;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingProjects->take(10) as $project)
                                        @php
                                            $daysPending = $project->days_pending ?? $project->created_at->diffInDays(now());
                                            $urgency = $project->urgency ?? ($daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low'));
                                            $urgencyClass = $urgency === 'urgent' ? 'danger' : ($urgency === 'normal' ? 'warning' : 'success');
                                            $urgencyBadge = $urgency === 'urgent' ? 'Urgent' : ($urgency === 'normal' ? 'Normal' : 'Low');
                                        @endphp
                                        <tr class="align-middle">
                                            <td>
                                                <a href="{{ route('coordinator.projects.show', $project->project_id) }}" class="text-decoration-none font-weight-bold">
                                                    {{ $project->project_id }}
                                                </a>
                                            </td>
                                            <td class="text-wrap" style="word-wrap: break-word; white-space: normal; max-width: 250px;">
                                                <small class="text-muted">{{ $project->project_title ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $project->user->name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $project->user->province ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <small>{{ $project->provincial->name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $daysPending }} days</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $urgencyBadge }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <a href="{{ route('coordinator.projects.show', $project->project_id) }}"
                                                       class="btn btn-sm btn-primary">
                                                        View
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-sm btn-success approve-project-btn"
                                                            data-project-id="{{ $project->project_id }}"
                                                            data-project-title="{{ $project->project_title }}">
                                                        Approve
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning revert-project-btn"
                                                            data-project-id="{{ $project->project_id }}"
                                                            data-project-title="{{ $project->project_title }}">
                                                        Revert
                                                    </button>
                                                    <a href="{{ route('coordinator.projects.downloadPdf', $project->project_id) }}"
                                                       class="btn btn-sm btn-secondary"
                                                       target="_blank">
                                                        Download PDF
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($pendingProjectsCount > 10)
                            <div class="text-center mt-3">
                                <a href="{{ route('coordinator.projects.list', ['status' => 'forwarded_to_coordinator']) }}" class="btn btn-sm btn-outline-primary">
                                    View All {{ $pendingProjectsCount }} Pending Projects
                                </a>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Reports Tab --}}
                <div class="tab-pane fade" id="reports-pending" role="tabpanel">
                    @if($pendingReports->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted">No pending reports</p>
                        </div>
                    @else
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-sm table-hover pending-approvals-table">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th style="width: 120px;">Report ID</th>
                                        <th style="width: 200px; min-width: 150px; max-width: 250px;">Project</th>
                                        <th style="width: 140px;">Executor/Applicant</th>
                                        <th style="width: 120px;">Province</th>
                                        <th style="width: 140px;">Provincial</th>
                                        <th style="width: 120px;">Days Pending</th>
                                        <th style="width: 100px;">Priority</th>
                                        <th style="width: auto;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingReports->take(10) as $report)
                                        @php
                                            $daysPending = $report->days_pending ?? $report->created_at->diffInDays(now());
                                            $urgency = $report->urgency ?? ($daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low'));
                                            $urgencyClass = $urgency === 'urgent' ? 'danger' : ($urgency === 'normal' ? 'warning' : 'success');
                                            $urgencyBadge = $urgency === 'urgent' ? 'Urgent' : ($urgency === 'normal' ? 'Normal' : 'Low');
                                        @endphp
                                        <tr class="align-middle">
                                            <td>
                                                <a href="{{ route('coordinator.monthly.report.show', $report->report_id) }}" class="text-decoration-none font-weight-bold">
                                                    {{ $report->report_id }}
                                                </a>
                                            </td>
                                            <td class="text-wrap" style="word-wrap: break-word; white-space: normal; max-width: 250px;">
                                                <small class="text-muted">{{ $report->project_title ?? $report->report_id ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $report->user->name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $report->user->province ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <small>{{ $report->provincial->name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $daysPending }} days</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $urgencyBadge }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <a href="{{ route('coordinator.monthly.report.show', $report->report_id) }}"
                                                       class="btn btn-sm btn-primary">
                                                        View
                                                    </a>
                                                    <form method="POST" action="{{ route('coordinator.report.approve', $report->report_id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this report?');">
                                                            Approve
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning revert-btn"
                                                            data-report-id="{{ $report->report_id }}"
                                                            data-report-title="{{ $report->project_title ?? $report->report_id }}">
                                                        Revert
                                                    </button>
                                                    <a href="{{ route('coordinator.monthly.report.downloadPdf', $report->report_id) }}"
                                                       class="btn btn-sm btn-secondary"
                                                       target="_blank">
                                                        Download PDF
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($pendingReportsCount > 10)
                            <div class="text-center mt-3">
                                <a href="{{ route('coordinator.report.list', ['status' => 'forwarded_to_coordinator']) }}" class="btn btn-sm btn-outline-primary">
                                    View All {{ $pendingReportsCount }} Pending Reports
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Revert Report Modal --}}
<div class="modal fade" id="revertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Revert Report to Provincial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="revertForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p><strong>Report ID:</strong> <span id="revertReportId"></span></p>
                    <p><strong>Project:</strong> <span id="revertProjectTitle"></span></p>
                    <div class="mb-3">
                        <label for="revert_reason" class="form-label">Reason for Revert *</label>
                        <textarea class="form-control auto-resize-textarea"
                                  id="revert_reason"
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

{{-- Approve Project Modal --}}
<div class="modal fade" id="approveProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveProjectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p><strong>Project ID:</strong> <span id="approveProjectId"></span></p>
                    <p><strong>Project Title:</strong> <span id="approveProjectTitle"></span></p>
                    <div class="alert alert-info">
                        <strong>Note:</strong> Set the Commencement Month & Year. It cannot be before the current month.
                    </div>
                    <div class="mb-3">
                        <label for="approve_commencement_month" class="form-label">Commencement Month *</label>
                        <select name="commencement_month" id="approve_commencement_month" class="form-control" required>
                            <option value="">Select month</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="approve_commencement_year" class="form-label">Commencement Year *</label>
                        <select name="commencement_year" id="approve_commencement_year" class="form-control" required>
                            <option value="">Select year</option>
                            @for($y = (int)date('Y'); $y <= (int)date('Y') + 10; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Revert Project Modal --}}
<div class="modal fade" id="revertProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Revert Project to Provincial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="revertProjectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p><strong>Project ID:</strong> <span id="revertProjectId"></span></p>
                    <p><strong>Project Title:</strong> <span id="revertProjectTitleText"></span></p>
                    <div class="mb-3">
                        <label for="revert_project_reason" class="form-label">Reason for Revert *</label>
                        <textarea class="form-control auto-resize-textarea"
                                  id="revert_project_reason"
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

{{-- Styles moved to public/css/custom/common-tables.css --}}

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize tabs
    const triggerTabList = document.querySelectorAll('#pendingApprovalsTabs button[data-bs-toggle="tab"]');
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

    // Report Revert button handler
    document.querySelectorAll('.revert-btn').forEach(button => {
        button.addEventListener('click', function() {
            const reportId = this.dataset.reportId;
            const reportTitle = this.dataset.reportTitle;

            document.getElementById('revertReportId').textContent = reportId;
            document.getElementById('revertProjectTitle').textContent = reportTitle;
            document.getElementById('revertForm').action = '{{ route("coordinator.report.revert", ":id") }}'.replace(':id', reportId);
            document.getElementById('revert_reason').value = '';

            const modal = new bootstrap.Modal(document.getElementById('revertModal'));
            modal.show();
        });
    });

    // Project Approve button handler
    document.querySelectorAll('.approve-project-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const projectId = this.dataset.projectId;
            const projectTitle = this.dataset.projectTitle || projectId;

            document.getElementById('approveProjectId').textContent = projectId;
            document.getElementById('approveProjectTitle').textContent = projectTitle;
            document.getElementById('approveProjectForm').action = '{{ route("projects.approve", ":id") }}'.replace(':id', projectId);
            document.getElementById('approve_commencement_month').value = '';
            document.getElementById('approve_commencement_year').value = '';

            const modal = new bootstrap.Modal(document.getElementById('approveProjectModal'));
            modal.show();
        });
    });

    // Project Revert button handler
    document.querySelectorAll('.revert-project-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const projectId = this.dataset.projectId;
            const projectTitle = this.dataset.projectTitle;

            document.getElementById('revertProjectId').textContent = projectId;
            document.getElementById('revertProjectTitleText').textContent = projectTitle;
            document.getElementById('revertProjectForm').action = '{{ route("projects.revertToProvincial", ":id") }}'.replace(':id', projectId);
            document.getElementById('revert_project_reason').value = '';

            const modal = new bootstrap.Modal(document.getElementById('revertProjectModal'));
            modal.show();
        });
    });

    // Auto-resize textarea
    document.querySelectorAll('.auto-resize-textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});
</script>
@endpush
