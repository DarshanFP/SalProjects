@php
    use App\Models\OldProjects\Project;
    use App\Constants\ProjectStatus;
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

                    {{-- Enhanced Projects Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Project ID</th>
                                    <th>Team Member</th>
                                    <th>Role</th>
                                    <th>Center</th>
                                    <th>Project Title</th>
                                    <th>Project Type</th>
                                    <th>Overall Project Budget</th>
                                    <th>Existing Funds</th>
                                    <th>Local Contribution</th>
                                    <th>Amount Requested</th>
                                    <th>Health</th>
                                    <th>Status</th>
                                    <th>Actions</th>
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
                                        <td>
                                            <a href="{{ route('provincial.projects.show', $project->project_id) }}"
                                               class="text-decoration-none fw-bold">
                                                {{ $project->project_id }}
                                            </a>
                                        </td>
                                        <td>
                                            <strong>{{ $project->user->name }}</strong>
                                            @if($project->user->email)
                                                <br><small class="text-muted">{{ $project->user->email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $project->user->role === 'executor' ? 'primary' : 'info' }}">
                                                {{ ucfirst($project->user->role) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $project->user->center ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <div class="text-wrap" style="max-width: 200px;">
                                                {{ $project->project_title }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $project->project_type }}</span>
                                        </td>
                                        <td class="text-end">
                                            <small>{{ format_indian_currency($project->overall_project_budget ?? 0, 2) }}</small>
                                        </td>
                                        @php
                                            $existingFunds = (float) ($project->amount_forwarded ?? 0);
                                            $localContribution = (float) ($project->local_contribution ?? 0);
                                            $overallBudget = (float) ($project->overall_project_budget ?? 0);
                                            $amountRequested = max(0, $overallBudget - $existingFunds - $localContribution);
                                        @endphp
                                        <td class="text-end">
                                            <small>{{ format_indian_currency($existingFunds, 2) }}</small>
                                        </td>
                                        <td class="text-end">
                                            <small>{{ format_indian_currency($localContribution, 2) }}</small>
                                        </td>
                                        <td class="text-end">
                                            <small>{{ format_indian_currency($amountRequested, 2) }}</small>
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
                                            <span class="badge {{ $statusBadgeClass }}">{{ Str::limit($statusLabel, 25) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
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
                                        <td colspan="13" class="text-center py-4">
                                            <i data-feather="inbox" class="text-muted" style="width: 48px; height: 48px;"></i>
                                            <p class="mt-3 text-muted">No projects found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
