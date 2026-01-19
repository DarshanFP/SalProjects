@php
    use App\Models\Reports\Monthly\DPReport;
    use App\Models\OldProjects\Project;
    use App\Constants\ProjectStatus;
@endphp
{{-- Pending Approvals Widget (Projects & Reports) --}}
<div class="card mb-4 widget-card" data-widget-id="pending-approvals">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="clock" class="me-2"></i>Pending Approvals
            @if($totalPendingCount > 0)
                <span class="badge bg-danger ms-2">{{ $totalPendingCount }}</span>
            @endif
        </h5>
        <div>
            <a href="{{ route('provincial.report.pending') }}" class="btn btn-sm btn-outline-primary me-2">View All Reports</a>
            <a href="{{ route('provincial.projects.list') }}" class="btn btn-sm btn-outline-primary me-2">View All Projects</a>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="pending-approvals" title="Minimize">
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if($totalPendingCount == 0)
            <div class="text-center py-4">
                <i data-feather="check-circle" class="text-success" style="width: 48px; height: 48px;"></i>
                <p class="mt-3 text-muted">No pending approvals</p>
            </div>
        @else
            {{-- Summary Cards --}}
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body p-3">
                            <small class="d-block">Total Pending</small>
                            <h4 class="mb-0">{{ $totalPendingCount }}</h4>
                            <small class="d-block mt-1">{{ $pendingProjectsCount }} Projects, {{ $pendingReportsCount }} Reports</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body p-3">
                            <small class="d-block">Urgent (>7 days)</small>
                            <h4 class="mb-0">{{ $urgentCount + $urgentProjectsCount }}</h4>
                            <small class="d-block mt-1">{{ $urgentProjectsCount }} Projects, {{ $urgentCount }} Reports</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body p-3">
                            <small class="d-block">Normal (3-7 days)</small>
                            <h4 class="mb-0">{{ $normalCount + $normalProjectsCount }}</h4>
                            <small class="d-block mt-1">{{ $normalProjectsCount }} Projects, {{ $normalCount }} Reports</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body p-3">
                            <small class="d-block">Recent (<3 days)</small>
                            <h4 class="mb-0">{{ $totalPendingCount - ($urgentCount + $urgentProjectsCount + $normalCount + $normalProjectsCount) }}</h4>
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
                        <i data-feather="folder" style="width: 16px; height: 16px;"></i> Projects
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
                        <i data-feather="file-text" style="width: 16px; height: 16px;"></i> Reports
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
                            <i data-feather="folder" class="text-muted" style="width: 48px; height: 48px;"></i>
                            <p class="mt-3 text-muted">No pending projects</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Title</th>
                                        <th>Team Member</th>
                                        <th>Center</th>
                                        <th>Days Pending</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingProjects->take(5) as $project)
                                        @php
                                            $daysPending = $project->days_pending ?? $project->created_at->diffInDays(now());
                                            $urgency = $project->urgency ?? ($daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low'));
                                            $urgencyClass = $urgency === 'urgent' ? 'danger' : ($urgency === 'normal' ? 'warning' : 'success');
                                            $urgencyBadge = $urgency === 'urgent' ? 'Urgent' : ($urgency === 'normal' ? 'Normal' : 'Low');
                                        @endphp
                                        <tr class="align-middle">
                                            <td>
                                                <a href="{{ route('provincial.projects.show', $project->project_id) }}" class="text-decoration-none">
                                                    {{ $project->project_id }}
                                                </a>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($project->project_title, 40) }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $project->user->name }}</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $project->user->center ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $daysPending }} days</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $urgencyBadge }}</span>
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
                                                            class="btn btn-sm btn-warning revert-project-btn" 
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

                        @if($pendingProjectsCount > 5)
                            <div class="text-center mt-3">
                                <a href="{{ route('provincial.projects.list') }}" class="btn btn-sm btn-outline-primary">
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
                            <i data-feather="file-text" class="text-muted" style="width: 48px; height: 48px;"></i>
                            <p class="mt-3 text-muted">No pending reports</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Report ID</th>
                                        <th>Project</th>
                                        <th>Team Member</th>
                                        <th>Center</th>
                                        <th>Days Pending</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingReports->take(5) as $report)
                                        @php
                                            $daysPending = $report->days_pending ?? $report->created_at->diffInDays(now());
                                            $urgency = $report->urgency ?? ($daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low'));
                                            $urgencyClass = $urgency === 'urgent' ? 'danger' : ($urgency === 'normal' ? 'warning' : 'success');
                                            $urgencyBadge = $urgency === 'urgent' ? 'Urgent' : ($urgency === 'normal' ? 'Normal' : 'Low');
                                        @endphp
                                        <tr class="align-middle">
                                            <td>
                                                <a href="{{ route('provincial.monthly.report.show', $report->report_id) }}" class="text-decoration-none">
                                                    {{ $report->report_id }}
                                                </a>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($report->project_title, 40) }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $report->user->name }}</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $report->user->center ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $daysPending }} days</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $urgencyClass }}">{{ $urgencyBadge }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <a href="{{ route('provincial.monthly.report.show', $report->report_id) }}" 
                                                       class="btn btn-sm btn-primary">
                                                        View
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-success approve-btn" 
                                                            data-report-id="{{ $report->report_id }}"
                                                            data-report-title="{{ $report->project_title }}">
                                                        Forward
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-warning revert-btn" 
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

                        @if($pendingReportsCount > 5)
                            <div class="text-center mt-3">
                                <a href="{{ route('provincial.report.pending') }}" class="btn btn-sm btn-outline-primary">
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

{{-- Approve Report Modal --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Forward Report to Coordinator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to forward this report to the coordinator?</p>
                    <p><strong>Report ID:</strong> <span id="approveReportId"></span></p>
                    <p><strong>Project:</strong> <span id="approveProjectTitle"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Forward to Coordinator</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Revert Report Modal --}}
<div class="modal fade" id="revertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Revert Report to Executor</h5>
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
                    <button type="submit" class="btn btn-warning">Revert to Executor</button>
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
                <h5 class="modal-title">Revert Project to Executor</h5>
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
                    <button type="submit" class="btn btn-warning">Revert to Executor</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    // Report Approve button handler
    document.querySelectorAll('.approve-btn').forEach(button => {
        button.addEventListener('click', function() {
            const reportId = this.dataset.reportId;
            const reportTitle = this.dataset.reportTitle;
            
            document.getElementById('approveReportId').textContent = reportId;
            document.getElementById('approveProjectTitle').textContent = reportTitle;
            document.getElementById('approveForm').action = '{{ route("provincial.report.forward", ":id") }}'.replace(':id', reportId);
            
            const modal = new bootstrap.Modal(document.getElementById('approveModal'));
            modal.show();
        });
    });

    // Report Revert button handler
    document.querySelectorAll('.revert-btn').forEach(button => {
        button.addEventListener('click', function() {
            const reportId = this.dataset.reportId;
            const reportTitle = this.dataset.reportTitle;
            
            document.getElementById('revertReportId').textContent = reportId;
            document.getElementById('revertProjectTitle').textContent = reportTitle;
            document.getElementById('revertForm').action = '{{ route("provincial.report.revert", ":id") }}'.replace(':id', reportId);
            document.getElementById('revert_reason').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('revertModal'));
            modal.show();
        });
    });

    // Project Revert button handler (with modal for reason)
    document.querySelectorAll('.revert-project-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const projectId = this.dataset.projectId;
            const projectTitle = this.dataset.projectTitle;
            
            document.getElementById('revertProjectId').textContent = projectId;
            document.getElementById('revertProjectTitleText').textContent = projectTitle;
            document.getElementById('revertProjectForm').action = '{{ route("projects.revertToExecutor", ":id") }}'.replace(':id', projectId);
            document.getElementById('revert_project_reason').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('revertProjectModal'));
            modal.show();
        });
    });
});
</script>
@endpush
