@php
    use App\Models\Reports\Monthly\DPReport;
    use Illuminate\Support\Str;
@endphp

{{-- Direct Team Overview Widget --}}
<div class="card mb-4 widget-card" data-widget-id="direct-team-overview">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Direct Team Overview</h5>
        <div>
            <a href="{{ route('general.executors') }}" class="btn btn-sm btn-outline-primary me-2">Manage Team</a>
            <a href="{{ route('general.createExecutor') }}" class="btn btn-sm btn-primary me-2">Add Member</a>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="direct-team-overview" title="Minimize">−</button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if(isset($directTeamOverviewData) && ($directTeamOverviewData['total_members'] ?? 0) > 0)
            {{-- Summary Statistics Cards --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Total Members</small>
                            <h3 class="mb-0">{{ $directTeamOverviewData['total_members'] ?? 0 }}</h3>
                            <small class="d-block mt-1">
                                {{ $directTeamOverviewData['active_members'] ?? 0 }} Active
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Active Members</small>
                            <h3 class="mb-0">{{ $directTeamOverviewData['active_members'] ?? 0 }}</h3>
                            <small class="d-block mt-1">
                                {{ $directTeamOverviewData['inactive_members'] ?? 0 }} Inactive
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">With Pending Items</small>
                            <h3 class="mb-0">{{ $directTeamOverviewData['members_with_pending'] ?? 0 }}</h3>
                            <small class="d-block mt-1">
                                Need attention
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Avg Projects</small>
                            <h3 class="mb-0">{{ $directTeamOverviewData['average_projects_per_member'] ?? 0 }}</h3>
                            <small class="d-block mt-1">
                                Per member
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
                            <small class="text-muted d-block">Total Projects</small>
                            <h5 class="mb-0">{{ $directTeamOverviewData['total_projects'] ?? 0 }}</h5>
                            <small class="text-muted">Approved projects</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Pending Projects</small>
                            <h5 class="mb-0 text-warning">{{ $directTeamOverviewData['total_pending_projects'] ?? 0 }}</h5>
                            <small class="text-muted">Awaiting approval</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Pending Reports</small>
                            <h5 class="mb-0 text-warning">{{ $directTeamOverviewData['total_pending_reports'] ?? 0 }}</h5>
                            <small class="text-muted">Awaiting approval</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Approved Reports</small>
                            <h5 class="mb-0 text-success">{{ $directTeamOverviewData['total_approved_reports'] ?? 0 }}</h5>
                            <small class="text-muted">Completed reports</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Team Members List Table --}}
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-sm">
                    <thead class="thead-light sticky-top">
                        <tr>
                            <th style="width: 150px;">Name</th>
                            <th style="width: 100px;">Role</th>
                            <th style="width: 120px;">Province</th>
                            <th style="width: 120px;">Center</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 80px;" class="text-center">Projects</th>
                            <th style="width: 100px;" class="text-center">Pending Projects</th>
                            <th style="width: 100px;" class="text-center">Pending Reports</th>
                            <th style="width: 100px;" class="text-center">Approved Reports</th>
                            <th style="width: 150px;">Last Activity</th>
                            <th style="width: auto;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($directTeamOverviewData['team_members'] ?? [] as $member)
                            <tr>
                                <td>
                                    <strong>{{ $member->name }}</strong>
                                    @if(($member->pending_projects_count ?? 0) > 0 || ($member->pending_reports_count ?? 0) > 0)
                                        <span class="badge bg-warning ms-1" title="Has pending items">!</span>
                                    @endif
                                    @if($member->email)
                                        <br><small class="text-muted">{{ Str::limit($member->email, 25) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $member->role === 'executor' ? 'primary' : 'info' }}">
                                        {{ ucfirst($member->role) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $member->province ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <small>{{ Str::limit($member->center ?? 'N/A', 20) }}</small>
                                </td>
                                <td>
                                    @if($member->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ $member->projects_count ?? 0 }}
                                </td>
                                <td class="text-center">
                                    {{ $member->pending_projects_count ?? 0 }}
                                </td>
                                <td class="text-center">
                                    {{ $member->pending_reports_count ?? 0 }}
                                </td>
                                <td class="text-center">
                                    {{ $member->reports_count ?? 0 }}
                                </td>
                                <td>
                                    @if($member->last_activity)
                                        <small>{{ \Carbon\Carbon::parse($member->last_activity)->diffForHumans() }}</small>
                                    @else
                                        <small class="text-muted">Never</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="{{ route('general.executors') }}" class="btn btn-sm btn-primary">View Details</a>
                                        <a href="{{ route('general.projects', ['user_id' => $member->id]) }}" class="btn btn-sm btn-info">Projects</a>
                                        <a href="{{ route('general.reports', ['user_id' => $member->id]) }}" class="btn btn-sm btn-secondary">Reports</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <p class="mt-2">No team members found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(($directTeamOverviewData['total_members'] ?? 0) > 12)
                <div class="text-center mt-3">
                    <a href="{{ route('general.executors') }}" class="btn btn-sm btn-outline-primary">
                        View All {{ $directTeamOverviewData['total_members'] }} Team Members
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-4">
                <p class="text-muted mt-3">No direct team members found. Add executors/applicants directly under you to get started.</p>
                <a href="{{ route('general.createExecutor') }}" class="btn btn-sm btn-primary">
Add Member
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
