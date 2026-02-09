@php
    use App\Models\Reports\Monthly\DPReport;
    use App\Models\OldProjects\Project;
    use App\Constants\ProjectStatus;

    $coordHierarchy = $pendingApprovalsData['coordinator_hierarchy'] ?? [];
    $directTeam = $pendingApprovalsData['direct_team'] ?? [];
    $all = $pendingApprovalsData['all'] ?? [];
    $totalPending = $pendingApprovalsData['total_pending'] ?? 0;
@endphp

{{-- Unified Pending Approvals Widget --}}
<div class="card mb-4 widget-card" data-widget-id="pending-approvals">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Pending Approvals
            @if($totalPending > 0)
                <span class="badge bg-danger ms-2">{{ $totalPending }}</span>
            @endif
        </h5>
        <div>
            <a href="{{ route('general.reports') }}" class="btn btn-sm btn-outline-primary me-2">View All Reports</a>
            <a href="{{ route('general.projects') }}" class="btn btn-sm btn-outline-primary me-2">View All Projects</a>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="pending-approvals" title="Minimize">−</button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if($totalPending == 0)
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
                            <h4 class="mb-0">{{ $totalPending }}</h4>
                            <small class="d-block mt-1">
                                {{ $all['projects_count'] ?? 0 }} Projects, {{ $all['reports_count'] ?? 0 }} Reports
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Urgent (>7 days)</small>
                            <h4 class="mb-0">{{ $pendingApprovalsData['total_urgent'] ?? 0 }}</h4>
                            <small class="d-block mt-1">
                                {{ ($all['urgent_projects'] ?? 0) }} Projects, {{ ($all['urgent_reports'] ?? 0) }} Reports
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Normal (3-7 days)</small>
                            <h4 class="mb-0">{{ $pendingApprovalsData['total_normal'] ?? 0 }}</h4>
                            <small class="d-block mt-1">
                                {{ ($all['normal_projects'] ?? 0) }} Projects, {{ ($all['normal_reports'] ?? 0) }} Reports
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Recent (<3 days)</small>
                            <h4 class="mb-0">{{ $totalPending - ($pendingApprovalsData['total_urgent'] ?? 0) - ($pendingApprovalsData['total_normal'] ?? 0) }}</h4>
                            <small class="d-block mt-1">Recently submitted</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Context Tabs --}}
            <ul class="nav nav-tabs mb-3" id="pendingApprovalsContextTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active"
                            id="coordinator-hierarchy-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#coordinator-hierarchy-pending"
                            type="button"
                            role="tab">
Coordinator Hierarchy
                        @if(($coordHierarchy['total_count'] ?? 0) > 0)
                            <span class="badge bg-danger ms-2">{{ $coordHierarchy['total_count'] ?? 0 }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                            id="direct-team-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#direct-team-pending"
                            type="button"
                            role="tab">
Direct Team
                        @if(($directTeam['total_count'] ?? 0) > 0)
                            <span class="badge bg-danger ms-2">{{ $directTeam['total_count'] ?? 0 }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                            id="all-pending-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#all-pending"
                            type="button"
                            role="tab">
All Pending
                        @if($totalPending > 0)
                            <span class="badge bg-danger ms-2">{{ $totalPending }}</span>
                        @endif
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="pendingApprovalsContextTabContent">
                {{-- Coordinator Hierarchy Tab --}}
                <div class="tab-pane fade show active" id="coordinator-hierarchy-pending" role="tabpanel">
                    @if(($coordHierarchy['total_count'] ?? 0) == 0)
                        <div class="text-center py-4">
                            <p class="text-muted">No pending approvals from Coordinator Hierarchy</p>
                        </div>
                    @else
                        {{-- Projects/Reports Sub-tabs --}}
                        <ul class="nav nav-tabs mb-3" id="coordHierarchySubTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active"
                                        id="coord-projects-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#coord-projects-pending"
                                        type="button"
                                        role="tab">
Projects
                                    <span class="badge bg-danger ms-2">{{ $coordHierarchy['projects_count'] ?? 0 }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link"
                                        id="coord-reports-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#coord-reports-pending"
                                        type="button"
                                        role="tab">
Reports
                                    <span class="badge bg-danger ms-2">{{ $coordHierarchy['reports_count'] ?? 0 }}</span>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="coordHierarchySubTabContent">
                            {{-- Coordinator Hierarchy Projects --}}
                            <div class="tab-pane fade show active" id="coord-projects-pending" role="tabpanel">
                                @if(($coordHierarchy['pending_projects'] ?? collect())->isEmpty())
                                    <div class="text-center py-4">
                                        <p class="mt-3 text-muted">No pending projects from Coordinator Hierarchy</p>
                                    </div>
                                @else
                                    @include('general.widgets.partials.pending-items-table', [
                                        'items' => $coordHierarchy['pending_projects']->take(10),
                                        'type' => 'project',
                                        'context' => 'coordinator_hierarchy'
                                    ])
                                    @if(($coordHierarchy['projects_count'] ?? 0) > 10)
                                        <div class="text-center mt-3">
                                            <a href="{{ route('general.projects', ['status' => 'forwarded_to_coordinator']) }}" class="btn btn-sm btn-outline-primary">
                                                View All {{ $coordHierarchy['projects_count'] }} Pending Projects
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            {{-- Coordinator Hierarchy Reports --}}
                            <div class="tab-pane fade" id="coord-reports-pending" role="tabpanel">
                                @if(($coordHierarchy['pending_reports'] ?? collect())->isEmpty())
                                    <div class="text-center py-4">
                                        <p class="mt-3 text-muted">No pending reports from Coordinator Hierarchy</p>
                                    </div>
                                @else
                                    @include('general.widgets.partials.pending-items-table', [
                                        'items' => $coordHierarchy['pending_reports']->take(10),
                                        'type' => 'report',
                                        'context' => 'coordinator_hierarchy'
                                    ])
                                    @if(($coordHierarchy['reports_count'] ?? 0) > 10)
                                        <div class="text-center mt-3">
                                            <a href="{{ route('general.reports', ['status' => 'forwarded_to_coordinator']) }}" class="btn btn-sm btn-outline-primary">
                                                View All {{ $coordHierarchy['reports_count'] }} Pending Reports
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Direct Team Tab --}}
                <div class="tab-pane fade" id="direct-team-pending" role="tabpanel">
                    @if(($directTeam['total_count'] ?? 0) == 0)
                        <div class="text-center py-4">
                            <p class="mt-3 text-muted">No pending approvals from Direct Team</p>
                        </div>
                    @else
                        {{-- Projects/Reports Sub-tabs --}}
                        <ul class="nav nav-tabs mb-3" id="directTeamSubTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active"
                                        id="direct-projects-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#direct-projects-pending"
                                        type="button"
                                        role="tab">
Projects
                                    <span class="badge bg-danger ms-2">{{ $directTeam['projects_count'] ?? 0 }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link"
                                        id="direct-reports-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#direct-reports-pending"
                                        type="button"
                                        role="tab">
Reports
                                    <span class="badge bg-danger ms-2">{{ $directTeam['reports_count'] ?? 0 }}</span>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="directTeamSubTabContent">
                            {{-- Direct Team Projects --}}
                            <div class="tab-pane fade show active" id="direct-projects-pending" role="tabpanel">
                                @if(($directTeam['pending_projects'] ?? collect())->isEmpty())
                                    <div class="text-center py-4">
                                        <p class="mt-3 text-muted">No pending projects from Direct Team</p>
                                    </div>
                                @else
                                    @include('general.widgets.partials.pending-items-table', [
                                        'items' => $directTeam['pending_projects']->take(10),
                                        'type' => 'project',
                                        'context' => 'direct_team'
                                    ])
                                    @if(($directTeam['projects_count'] ?? 0) > 10)
                                        <div class="text-center mt-3">
                                            <a href="{{ route('general.projects', ['status' => 'submitted_to_provincial']) }}" class="btn btn-sm btn-outline-primary">
                                                View All {{ $directTeam['projects_count'] }} Pending Projects
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            {{-- Direct Team Reports --}}
                            <div class="tab-pane fade" id="direct-reports-pending" role="tabpanel">
                                @if(($directTeam['pending_reports'] ?? collect())->isEmpty())
                                    <div class="text-center py-4">
                                        <p class="mt-3 text-muted">No pending reports from Direct Team</p>
                                    </div>
                                @else
                                    @include('general.widgets.partials.pending-items-table', [
                                        'items' => $directTeam['pending_reports']->take(10),
                                        'type' => 'report',
                                        'context' => 'direct_team'
                                    ])
                                    @if(($directTeam['reports_count'] ?? 0) > 10)
                                        <div class="text-center mt-3">
                                            <a href="{{ route('general.reports', ['status' => 'submitted_to_provincial']) }}" class="btn btn-sm btn-outline-primary">
                                                View All {{ $directTeam['reports_count'] }} Pending Reports
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- All Pending Tab (Unified View) --}}
                <div class="tab-pane fade" id="all-pending" role="tabpanel">
                    {{-- Projects/Reports Sub-tabs --}}
                    <ul class="nav nav-tabs mb-3" id="allSubTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active"
                                    id="all-projects-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#all-projects-pending"
                                    type="button"
                                    role="tab">
Projects
                                <span class="badge bg-danger ms-2">{{ $all['projects_count'] ?? 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link"
                                    id="all-reports-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#all-reports-pending"
                                    type="button"
                                    role="tab">
Reports
                                <span class="badge bg-danger ms-2">{{ $all['reports_count'] ?? 0 }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="allSubTabContent">
                        {{-- All Projects --}}
                        <div class="tab-pane fade show active" id="all-projects-pending" role="tabpanel">
                            @if(($all['pending_projects'] ?? collect())->isEmpty())
                                <div class="text-center py-4">
                                    <p class="mt-3 text-muted">No pending projects</p>
                                </div>
                            @else
                                @include('general.widgets.partials.pending-items-table', [
                                    'items' => $all['pending_projects']->take(10),
                                    'type' => 'project',
                                    'context' => 'all'
                                ])
                                @if(($all['projects_count'] ?? 0) > 10)
                                    <div class="text-center mt-3">
                                        <a href="{{ route('general.projects') }}" class="btn btn-sm btn-outline-primary">
                                            View All {{ $all['projects_count'] }} Pending Projects
                                        </a>
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- All Reports --}}
                        <div class="tab-pane fade" id="all-reports-pending" role="tabpanel">
                            @if(($all['pending_reports'] ?? collect())->isEmpty())
                                <div class="text-center py-4">
                                    <p class="mt-3 text-muted">No pending reports</p>
                                </div>
                            @else
                                @include('general.widgets.partials.pending-items-table', [
                                    'items' => $all['pending_reports']->take(10),
                                    'type' => 'report',
                                    'context' => 'all'
                                ])
                                @if(($all['reports_count'] ?? 0) > 10)
                                    <div class="text-center mt-3">
                                        <a href="{{ route('general.reports') }}" class="btn btn-sm btn-outline-primary">
                                            View All {{ $all['reports_count'] }} Pending Reports
                                        </a>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Approve Project Modal (with Commencement Date for Coordinator Context) --}}
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
                    <input type="hidden" name="approval_context" id="approveProjectContext">
                    <div id="commencementDateFields" style="display: none;">
                        <div class="mb-3">
                            <label for="commencement_month" class="form-label">Commencement Month *</label>
                            <select name="commencement_month" id="commencement_month" class="form-select" required>
                                <option value="">Select Month</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="commencement_year" class="form-label">Commencement Year *</label>
                            <select name="commencement_year" id="commencement_year" class="form-select" required>
                                <option value="">Select Year</option>
                                @for($i = date('Y'); $i <= date('Y') + 5; $i++)
                                    <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Revert Item Modal --}}
<div class="modal fade" id="revertItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Revert <span id="revertItemTypeLabel"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="revertItemForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p><strong><span id="revertItemIdLabel"></span>:</strong> <span id="revertItemId"></span></p>
                    <p><strong><span id="revertItemTitleLabel"></span>:</strong> <span id="revertItemTitle"></span></p>
                    <input type="hidden" name="approval_context" id="revertItemContext">
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
                    <button type="submit" class="btn btn-warning">Revert</button>
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

    // Initialize context tabs
    const contextTabList = document.querySelectorAll('#pendingApprovalsContextTabs button[data-bs-toggle="tab"]');
    contextTabList.forEach(triggerEl => {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener('click', event => {
            event.preventDefault();
            tabTrigger.show();
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    });

    // Initialize sub-tabs for each context
    ['coordHierarchy', 'directTeam', 'all'].forEach(context => {
        const subTabList = document.querySelectorAll(`#${context}SubTabs button[data-bs-toggle="tab"]`);
        subTabList.forEach(triggerEl => {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            triggerEl.addEventListener('click', event => {
                event.preventDefault();
                tabTrigger.show();
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });
        });
    });

    // Approve Project button handler (with commencement date for coordinator context)
    document.querySelectorAll('.approve-project-btn').forEach(button => {
        button.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            const approvalContext = this.dataset.approvalContext;

            document.getElementById('approveProjectId').textContent = projectId;
            document.getElementById('approveProjectContext').value = approvalContext;

            // Get project title from the row
            const row = this.closest('tr');
            const titleCell = row.querySelector('td.text-wrap small');
            document.getElementById('approveProjectTitle').textContent = titleCell ? titleCell.textContent.trim() : projectId;

            document.getElementById('approveProjectForm').action = '{{ route("general.approveProject", ":id") }}'.replace(':id', projectId);

            // Show/hide commencement date fields based on context
            const commencementFields = document.getElementById('commencementDateFields');
            if (approvalContext === 'coordinator') {
                commencementFields.style.display = 'block';
                document.getElementById('commencement_month').required = true;
                document.getElementById('commencement_year').required = true;
            } else {
                commencementFields.style.display = 'none';
                document.getElementById('commencement_month').required = false;
                document.getElementById('commencement_year').required = false;
            }

            const modal = new bootstrap.Modal(document.getElementById('approveProjectModal'));
            modal.show();
        });
    });

    // Revert Item button handler
    document.querySelectorAll('.revert-item-btn').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const itemTitle = this.dataset.itemTitle;
            const itemType = this.dataset.itemType;
            const approvalContext = this.dataset.approvalContext;

            document.getElementById('revertItemId').textContent = itemId;
            document.getElementById('revertItemTitle').textContent = itemTitle;
            document.getElementById('revertItemContext').value = approvalContext;
            document.getElementById('revertItemTypeLabel').textContent = itemType === 'project' ? 'Project' : 'Report';
            document.getElementById('revertItemIdLabel').textContent = itemType === 'project' ? 'Project ID' : 'Report ID';
            document.getElementById('revertItemTitleLabel').textContent = itemType === 'project' ? 'Project Title' : 'Project';

            const routeName = itemType === 'project'
                ? '{{ route("general.revertProject", ":id") }}'
                : '{{ route("general.revertReport", ":id") }}';
            document.getElementById('revertItemForm').action = routeName.replace(':id', itemId);
            document.getElementById('revert_reason').value = '';

            const modal = new bootstrap.Modal(document.getElementById('revertItemModal'));
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

