{{-- Coordinator Overview Widget --}}
<div class="card mb-4 widget-card" data-widget-id="coordinator-overview">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="users" class="me-2"></i>Coordinator Overview
        </h5>
        <div>
            <a href="{{ route('general.coordinators') }}" class="btn btn-sm btn-outline-primary me-2">View All Coordinators</a>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="coordinator-overview" title="Minimize">
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if(isset($coordinatorOverviewData) && ($coordinatorOverviewData['total_coordinators'] ?? 0) > 0)
            {{-- Summary Statistics Cards --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Total Coordinators</small>
                            <h3 class="mb-0">{{ $coordinatorOverviewData['total_coordinators'] ?? 0 }}</h3>
                            <small class="d-block mt-1">
                                {{ $coordinatorOverviewData['active_coordinators'] ?? 0 }} Active
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Active Coordinators</small>
                            <h3 class="mb-0">{{ $coordinatorOverviewData['active_coordinators'] ?? 0 }}</h3>
                            <small class="d-block mt-1">
                                {{ $coordinatorOverviewData['inactive_coordinators'] ?? 0 }} Inactive
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">With Pending Items</small>
                            <h3 class="mb-0">{{ $coordinatorOverviewData['coordinators_with_pending'] ?? 0 }}</h3>
                            <small class="d-block mt-1">
                                Need attention
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Avg Team Size</small>
                            <h3 class="mb-0">{{ $coordinatorOverviewData['average_team_size'] ?? 0 }}</h3>
                            <small class="d-block mt-1">
                                Per coordinator
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Additional Statistics Row --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Total Team Members</small>
                            <h5 class="mb-0">{{ $coordinatorOverviewData['total_team_members'] ?? 0 }}</h5>
                            <small class="text-muted">Across all coordinators</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Total Projects</small>
                            <h5 class="mb-0">{{ $coordinatorOverviewData['total_projects'] ?? 0 }}</h5>
                            <small class="text-muted">Approved projects</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Pending Projects</small>
                            <h5 class="mb-0 text-warning">{{ $coordinatorOverviewData['total_pending_projects'] ?? 0 }}</h5>
                            <small class="text-muted">Awaiting approval</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Pending Reports</small>
                            <h5 class="mb-0 text-warning">{{ $coordinatorOverviewData['total_pending_reports'] ?? 0 }}</h5>
                            <small class="text-muted">Awaiting approval</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Coordinator List Table --}}
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-sm">
                    <thead class="thead-light sticky-top">
                        <tr>
                            <th style="width: 150px;">Name</th>
                            <th style="width: 120px;">Province</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 120px;">Team Members</th>
                            <th style="width: 100px;">Projects</th>
                            <th style="width: 120px;">Pending Projects</th>
                            <th style="width: 120px;">Pending Reports</th>
                            <th style="width: 120px;">Approved Reports</th>
                            <th style="width: 150px;">Last Activity</th>
                            <th style="width: auto;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coordinatorOverviewData['coordinators'] ?? [] as $coordinator)
                            <tr>
                                <td>
                                    <strong>{{ $coordinator->name }}</strong>
                                    @if(($coordinator->pending_projects_count ?? 0) > 0 || ($coordinator->pending_reports_count ?? 0) > 0)
                                        <span class="badge bg-warning ms-1" title="Has pending items">!</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $coordinator->province ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @if($coordinator->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $coordinator->team_members_count ?? 0 }}
                                </td>
                                <td>
                                    {{ $coordinator->projects_count ?? 0 }}
                                </td>
                                <td>
                                    {{ $coordinator->pending_projects_count ?? 0 }}
                                </td>
                                <td>
                                    {{ $coordinator->pending_reports_count ?? 0 }}
                                </td>
                                <td>
                                    {{ $coordinator->approved_reports_count ?? 0 }}
                                </td>
                                <td>
                                    @if($coordinator->last_activity)
                                        <small>{{ \Carbon\Carbon::parse($coordinator->last_activity)->diffForHumans() }}</small>
                                    @else
                                        <small class="text-muted">Never</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="{{ route('general.coordinators') }}" class="btn btn-sm btn-primary">View Details</a>
                                        <a href="{{ route('coordinator.projects.list') }}" class="btn btn-sm btn-info">Projects</a>
                                        <a href="{{ route('coordinator.report.list') }}" class="btn btn-sm btn-secondary">Reports</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i data-feather="users" style="width: 48px; height: 48px;" class="text-muted"></i>
                                    <p class="mt-2">No coordinators found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(($coordinatorOverviewData['total_coordinators'] ?? 0) > 12)
                <div class="text-center mt-3">
                    <a href="{{ route('general.coordinators') }}" class="btn btn-sm btn-outline-primary">
                        View All {{ $coordinatorOverviewData['total_coordinators'] }} Coordinators
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-4">
                <i data-feather="users" class="text-muted" style="width: 48px; height: 48px;"></i>
                <p class="text-muted mt-3">No coordinators found. Create your first coordinator to get started.</p>
                <a href="{{ route('general.createCoordinator') }}" class="btn btn-sm btn-primary">
                    <i data-feather="plus"></i> Create Coordinator
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endpush
