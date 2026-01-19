{{-- Provincial Overview Widget --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="feather icon-users text-primary"></i> Provincial Overview
        </h5>
        <a href="{{ route('coordinator.provincials') }}" class="btn btn-sm btn-primary">
            View All <i class="feather icon-arrow-right"></i>
        </a>
    </div>
    <div class="card-body">
        @if(isset($provincialOverviewData))
            {{-- Summary Statistics --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body p-3">
                            <small class="d-block mb-1">Total Provincials</small>
                            <h3 class="mb-0">{{ $provincialOverviewData['total_provincials'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body p-3">
                            <small class="d-block mb-1">Active</small>
                            <h3 class="mb-0">{{ $provincialOverviewData['active_provincials'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body p-3">
                            <small class="d-block mb-1">Team Members</small>
                            <h3 class="mb-0">{{ $provincialOverviewData['total_team_members'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body p-3">
                            <small class="d-block mb-1">Pending Reports</small>
                            <h3 class="mb-0">{{ $provincialOverviewData['total_pending_reports'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Additional Statistics --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Total Projects</small>
                            <h5 class="mb-0">{{ $provincialOverviewData['total_projects'] ?? 0 }}</h5>
                            <small class="text-muted">Avg: {{ $provincialOverviewData['average_projects_per_provincial'] ?? 0 }}/provincial</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Approved Reports</small>
                            <h5 class="mb-0">{{ $provincialOverviewData['total_approved_reports'] ?? 0 }}</h5>
                            <small class="text-muted">Avg: {{ $provincialOverviewData['average_reports_per_provincial'] ?? 0 }}/provincial</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Inactive Provincials</small>
                            <h5 class="mb-0">{{ $provincialOverviewData['inactive_provincials'] ?? 0 }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Provincial List --}}
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-sm">
                    <thead class="thead-light sticky-top">
                        <tr>
                            <th>Name</th>
                            <th>Province</th>
                            <th>Center</th>
                            <th>Status</th>
                            <th>Team Members</th>
                            <th>Projects</th>
                            <th>Pending Reports</th>
                            <th>Approved Reports</th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($provincialOverviewData['provincials'] ?? [] as $provincial)
                            <tr>
                                <td>
                                    <strong>{{ $provincial->name }}</strong>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ $provincial->province ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $provincial->center ?? 'N/A' }}</td>
                                <td>
                                    @if($provincial->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $provincial->children_count ?? 0 }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $provincial->projects_count ?? 0 }}</span>
                                </td>
                                <td>
                                    @if(($provincial->team_reports_pending ?? 0) > 0)
                                        <span class="badge badge-warning">{{ $provincial->team_reports_pending }}</span>
                                    @else
                                        <span class="badge badge-success">0</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-success">{{ $provincial->team_reports_approved ?? 0 }}</span>
                                </td>
                                <td>
                                    @if($provincial->last_activity)
                                        <small>{{ \Carbon\Carbon::parse($provincial->last_activity)->diffForHumans() }}</small>
                                    @else
                                        <small class="text-muted">Never</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('coordinator.editProvincial', $provincial->id) }}" class="btn btn-sm btn-info">
                                            View Details
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">No provincials found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="feather icon-users text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No provincial data available</p>
            </div>
        @endif
    </div>
</div>
