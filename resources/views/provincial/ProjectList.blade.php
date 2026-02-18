@php
    use App\Models\OldProjects\Project;
    use App\Constants\ProjectStatus;
    use App\Helpers\TableFormatter;
    use App\Helpers\ProjectPermissionHelper;
    use App\Models\Reports\Monthly\DPReport;
    use Illuminate\Support\Str;
@endphp
{{-- Enhanced Project List for Provincial --}}
@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i data-feather="folder" class="me-2"></i>All Team Projects
                    </h4>
                    <div>
                        @if(isset($statusDistribution) && $statusDistribution->count() > 0)
                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#statusChartModal">
                                <i data-feather="pie-chart"></i> View Status Distribution
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    {{-- Enhanced Filters --}}
                    <form method="GET" action="{{ route('provincial.projects.list') }}" class="mb-4">
                        <div class="row g-3">
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
                            <div class="col-md-3">
                                <label for="user_id" class="form-label">Team Member</label>
                                <select name="user_id" id="user_id" class="form-select">
                                    <option value="">All Team Members</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ ucfirst($user->role) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    @foreach(Project::$statusLabels as $key => $label)
                                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                            {{ Str::limit($label, 30) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="center" class="form-label">Center</label>
                                <select name="center" id="center" class="form-select">
                                    <option value="">All Centers</option>
                                    @foreach($centers ?? [] as $center)
                                        <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                            {{ $center }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply</button>
                                <a href="{{ route('provincial.projects.list') }}" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>

                    {{-- Page size selector and Export --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <form method="GET" action="{{ route('provincial.projects.list') }}" class="d-flex align-items-center gap-2">
                            @foreach(request()->except('per_page', 'page') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <label for="per_page" class="form-label mb-0">Per page</label>
                            <select name="per_page" id="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                @foreach($allowedPageSizes ?? TableFormatter::ALLOWED_PAGE_SIZES as $size)
                                    <option value="{{ $size }}" {{ ($currentPerPage ?? 25) == $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                        </form>
                        <a href="{{ route('provincial.projects.export', request()->query()) }}" class="btn btn-sm btn-success">
                            <i data-feather="download"></i> Download Excel
                        </a>
                    </div>

                    {{-- Summary block above table --}}
                    @if(isset($grandTotals) && isset($totalRecordCount))
                    <div class="card mb-3">
                        <div class="card-body py-3">
                            <div class="row g-3 text-center">
                                <div class="col">
                                    <span class="text-muted small">Total Records</span>
                                    <div class="fw-bold">{{ number_format($totalRecordCount) }}</div>
                                </div>
                                <div class="col">
                                    <span class="text-muted small">Total Overall Budget</span>
                                    <div class="fw-bold">{{ format_indian_currency($grandTotals['overall_project_budget'] ?? 0, 2) }}</div>
                                </div>
                                <div class="col">
                                    <span class="text-muted small">Total Existing Funds</span>
                                    <div class="fw-bold">{{ format_indian_currency($grandTotals['amount_forwarded'] ?? 0, 2) }}</div>
                                </div>
                                <div class="col">
                                    <span class="text-muted small">Total Local Contribution</span>
                                    <div class="fw-bold">{{ format_indian_currency($grandTotals['local_contribution'] ?? 0, 2) }}</div>
                                </div>
                                <div class="col">
                                    <span class="text-muted small">Total Amount Sanctioned (Approved)</span>
                                    <div class="fw-bold">{{ format_indian_currency($grandTotals['amount_sanctioned'] ?? 0, 2) }}</div>
                                </div>
                                <div class="col">
                                    <span class="text-muted small">Total Amount Requested (Pending)</span>
                                    <div class="fw-bold">{{ format_indian_currency($grandTotals['amount_requested'] ?? 0, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Status Summary Cards --}}
                    @if(isset($statusDistribution) && $statusDistribution->count() > 0)
                    <div class="row mb-4">
                        @foreach($statusDistribution->take(6) as $status => $count)
                            @php
                                $statusLabel = Project::$statusLabels[$status] ?? $status;
                                $badgeClass = [
                                    'draft' => 'bg-secondary',
                                    'submitted_to_provincial' => 'bg-primary',
                                    'reverted_by_provincial' => 'bg-warning',
                                    'forwarded_to_coordinator' => 'bg-info',
                                    'reverted_by_coordinator' => 'bg-warning',
                                    'approved_by_coordinator' => 'bg-success',
                                    'rejected_by_coordinator' => 'bg-danger',
                                ][$status] ?? 'bg-secondary';
                            @endphp
                            <div class="col-md-2">
                                <div class="card text-center">
                                    <div class="card-body p-2">
                                        <span class="badge {{ $badgeClass }} mb-2">{{ Str::limit($statusLabel, 20) }}</span>
                                        <h5 class="mb-0">{{ $count }}</h5>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Enhanced Projects Table (header-driven layout) --}}
                    <style>
                        .provincial-project-list-table {
                            table-layout: auto;
                            width: 100%;
                        }
                        .provincial-project-list-table th,
                        .provincial-project-list-table td {
                            padding: 0.4rem 0.5rem;
                            vertical-align: middle;
                            min-width: fit-content;
                        }
                        .provincial-project-list-table th {
                            white-space: nowrap;
                            font-weight: 600;
                        }
                        .provincial-project-list-table td {
                            white-space: normal;
                            overflow-wrap: break-word;
                            word-break: break-word;
                        }
                        .provincial-project-list-table .col-actions {
                            width: 160px;
                            min-width: 160px;
                            max-width: 160px;
                        }
                        .provincial-project-list-table .btn {
                            padding: 0.25rem 0.5rem;
                            font-size: 0.75rem;
                            line-height: 1.2;
                        }
                        .provincial-project-list-table .actions-wrapper {
                            display: flex;
                            gap: 6px;
                            flex-wrap: nowrap;
                            align-items: center;
                        }
                        .provincial-project-list-table .text-cell {
                            display: -webkit-box;
                            -webkit-line-clamp: 2;
                            -webkit-box-orient: vertical;
                            overflow: hidden;
                        }
                        .provincial-project-list-table .text-wrap-cell {
                            display: block;
                            max-width: 100%;
                        }
                        .provincial-project-list-table .badge {
                            white-space: normal;
                        }
                    </style>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover provincial-project-list-table">
                            <thead class="table-light">
                                <tr>
                                    <th>S.No</th>
                                    <th>Project ID</th>
                                    <th>Team Member</th>
                                    <th>Role</th>
                                    <th>Center</th>
                                    <th>Society</th>
                                    <th>Project Title</th>
                                    <th>Project Type</th>
                                    <th>Overall Project Budget</th>
                                    <th>Existing Funds</th>
                                    <th>Local Contribution</th>
                                    <th>Requested / Sanctioned</th>
                                    <th>Health</th>
                                    <th>Status</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    @php
                                        $statusLabel = Project::$statusLabels[$project->status] ?? $project->status;
                                        $statusBadgeClass = [
                                            ProjectStatus::DRAFT => 'bg-secondary',
                                            ProjectStatus::SUBMITTED_TO_PROVINCIAL => 'bg-primary',
                                            ProjectStatus::REVERTED_BY_PROVINCIAL => 'bg-warning',
                                            ProjectStatus::FORWARDED_TO_COORDINATOR => 'bg-info',
                                            ProjectStatus::REVERTED_BY_COORDINATOR => 'bg-warning',
                                            ProjectStatus::APPROVED_BY_COORDINATOR => 'bg-success',
                                            ProjectStatus::REJECTED_BY_COORDINATOR => 'bg-danger',
                                        ][$project->status] ?? 'bg-secondary';

                                        $healthBadge = [
                                            'good' => ['class' => 'bg-success', 'icon' => 'check-circle', 'label' => 'Good'],
                                            'warning' => ['class' => 'bg-warning', 'icon' => 'alert-triangle', 'label' => 'Warning'],
                                            'critical' => ['class' => 'bg-danger', 'icon' => 'alert-circle', 'label' => 'Critical'],
                                        ][$project->health_status ?? 'good'] ?? ['class' => 'bg-secondary', 'icon' => 'help-circle', 'label' => 'N/A'];
                                    @endphp
                                    <tr class="align-middle">
                                        <td>{{ \App\Helpers\TableFormatter::resolveSerial($loop, $projects, $projects->hasPages()) }}</td>
                                        <td>
                                            <a href="{{ route('projects.show', $project->project_id) }}"
                                               class="text-decoration-none fw-bold">
                                                {{ $project->project_id }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="text-cell"
                                                 data-bs-toggle="tooltip"
                                                 title="{{ $project->user->name }}{{ $project->user->email ? ' — ' . $project->user->email : '' }}">
                                                <strong>{{ $project->user->name }}</strong>
                                                @if($project->user->email)
                                                    <br><small class="text-muted">{{ $project->user->email }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-cell"
                                                 data-bs-toggle="tooltip"
                                                 title="{{ ucfirst($project->user->role) }}">
                                                {{ ucfirst($project->user->role) }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-cell"
                                                 data-bs-toggle="tooltip"
                                                 title="{{ $project->user->center ?? 'N/A' }}">
                                                <small>{{ $project->user->center ?? 'N/A' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1 flex-nowrap">
                                                <div class="text-cell flex-grow-1 min-w-0"
                                                     data-bs-toggle="tooltip"
                                                     title="{{ $project->society_name ?? '—' }}">
                                                    <small>{{ $project->society_name ?? '—' }}</small>
                                                </div>
                                                @if(ProjectPermissionHelper::canEdit($project, auth()->user()))
                                                    <button type="button"
                                                            class="btn btn-link text-muted p-0 border-0 align-baseline"
                                                            style="font-size: 0.75rem; min-width: 1.5rem;"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#updateSocietyModal"
                                                            data-project-id="{{ $project->project_id }}"
                                                            data-project-title="{{ Str::limit($project->project_title ?? $project->project_id, 40) }}"
                                                            data-update-url="{{ route('provincial.projects.updateSociety', $project->project_id) }}"
                                                            title="Update Society">
                                                        <i data-feather="edit-2" style="width: 12px; height: 12px;"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted" title="Project not editable">
                                                        <i data-feather="lock" style="width: 12px; height: 12px;"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-cell"
                                                 data-bs-toggle="tooltip"
                                                 title="{{ $project->project_title }}">
                                                {{ $project->project_title }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-wrap-cell"
                                                 data-bs-toggle="tooltip"
                                                 title="{{ $project->project_type }}">
                                                <span class="badge bg-secondary">{{ $project->project_type }}</span>
                                            </div>
                                        </td>
                                        @php
                                            $fin = $resolvedFinancials[$project->project_id] ?? [];
                                            $overallBudget = (float) ($fin['overall_project_budget'] ?? 0);
                                            $existingFunds = (float) ($fin['amount_forwarded'] ?? 0);
                                            $localContribution = (float) ($fin['local_contribution'] ?? 0);
                                            // M3.7 Phase 2: Approved → show sanctioned; non-approved → show requested
                                            $requestedOrSanctioned = $project->isApproved()
                                                ? (float) ($fin['amount_sanctioned'] ?? 0)
                                                : (float) ($fin['amount_requested'] ?? 0);
                                        @endphp
                                        <td class="text-end">
                                            <small>{{ format_indian_currency($overallBudget, 2) }}</small>
                                        </td>
                                        <td class="text-end">
                                            <small>{{ format_indian_currency($existingFunds, 2) }}</small>
                                        </td>
                                        <td class="text-end">
                                            <small>{{ format_indian_currency($localContribution, 2) }}</small>
                                        </td>
                                        <td class="text-end">
                                            <small>{{ format_indian_currency($requestedOrSanctioned, 2) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $healthBadge['class'] }}"
                                                  data-bs-toggle="tooltip"
                                                  title="Budget Utilization: {{ format_indian_percentage($project->budget_utilization ?? 0, 1) }}">
                                                <i data-feather="{{ $healthBadge['icon'] }}" style="width: 14px; height: 14px;"></i>
                                                {{ $healthBadge['label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-cell"
                                                 data-bs-toggle="tooltip"
                                                 title="{{ $statusLabel }}">
                                                {{ $statusLabel }}
                                            </div>
                                        </td>
                                        <td class="col-actions">
                                            <div class="actions-wrapper">
                                                <a href="{{ route('provincial.projects.show', $project->project_id) }}"
                                                   class="btn btn-sm btn-primary">
                                                    View
                                                </a>
                                                @if(in_array($project->status, [ProjectStatus::SUBMITTED_TO_PROVINCIAL, ProjectStatus::REVERTED_BY_COORDINATOR]))
                                                    @if($project->status === ProjectStatus::SUBMITTED_TO_PROVINCIAL)
                                                        <form method="POST"
                                                              action="{{ route('projects.forwardToCoordinator', $project->project_id) }}"
                                                              class="d-inline"
                                                              onsubmit="return confirm('Forward this project to coordinator?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                Forward
                                                            </button>
                                                        </form>
                                                        <form method="POST"
                                                              action="{{ route('projects.revertToExecutor', $project->project_id) }}"
                                                              class="d-inline"
                                                              onsubmit="return confirm('Revert this project to executor?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-warning">
                                                                Revert
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="15" class="text-center py-4">
                                            <i data-feather="inbox" class="text-muted" style="width: 48px; height: 48px;"></i>
                                            <p class="mt-3 text-muted">No projects found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(isset($projects) && method_exists($projects, 'links'))
                        <div class="card-footer d-flex justify-content-end">
                            {{ $projects->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Wave 5C: Update Society Modal --}}
<div class="modal fade" id="updateSocietyModal" tabindex="-1" aria-labelledby="updateSocietyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateSocietyModalLabel">Update Society</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateSocietyForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="updateSocietyProjectInfo">—</p>
                    <label for="updateSocietySocietyId" class="form-label">Society</label>
                    <select name="society_id" id="updateSocietySocietyId" class="form-select" required>
                        <option value="">Select society...</option>
                        @foreach($societies ?? [] as $society)
                            <option value="{{ $society->id }}">{{ $society->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Society</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('updateSocietyModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function(event) {
            var btn = event.relatedTarget;
            if (btn && btn.dataset.updateUrl) {
                document.getElementById('updateSocietyForm').action = btn.dataset.updateUrl;
                var info = document.getElementById('updateSocietyProjectInfo');
                info.textContent = 'Project: ' + (btn.dataset.projectTitle || btn.dataset.projectId || '—');
            }
        });
    }
});
</script>

{{-- Status Distribution Chart Modal --}}
@if(isset($statusDistribution) && $statusDistribution->count() > 0)
<div class="modal fade" id="statusChartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Project Status Distribution</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="projectStatusDistributionChart" style="min-height: 400px;"></div>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Status Distribution Chart
    @if(isset($statusDistribution) && $statusDistribution->count() > 0)
    if (typeof ApexCharts !== 'undefined' && document.querySelector("#projectStatusDistributionChart")) {
        const statusData = @json($statusDistribution);
        const statusLabels = Object.keys(statusData).map(status => {
            const labels = {
                'draft': 'Draft',
                'submitted_to_provincial': 'Submitted',
                'reverted_by_provincial': 'Reverted (Provincial)',
                'forwarded_to_coordinator': 'Forwarded',
                'reverted_by_coordinator': 'Reverted (Coordinator)',
                'approved_by_coordinator': 'Approved',
                'rejected_by_coordinator': 'Rejected'
            };
            return labels[status] ?? status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        });

        const statusChart = new ApexCharts(document.querySelector("#projectStatusDistributionChart"), {
            series: Object.values(statusData),
            chart: {
                type: 'donut',
                height: 400,
                foreColor: '#d0d6e1'
            },
            labels: statusLabels,
            colors: ['#6571ff', '#05a34a', '#fbbc06', '#ff3366', '#66d1d1', '#ec4899', '#10b981'],
            legend: {
                position: 'bottom',
                labels: {
                    colors: '#d0d6e1'
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val) {
                        return val + ' project' + (val !== 1 ? 's' : '');
                    }
                }
            }
        });
        statusChart.render();
    }
    @endif
});
</script>
@endpush
@endsection
