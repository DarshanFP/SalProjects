@php
    use App\Models\Reports\Monthly\DPReport;
    use Illuminate\Support\Str;
@endphp
{{-- Team Overview Widget (Enhanced) --}}
<div class="card mb-4 widget-card" data-widget-id="team-overview">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Team Overview</h5>
        <div>
            <select class="form-select form-select-sm d-inline-block" id="teamOverviewFilter" style="width: auto;">
                <option value="">All Members</option>
                <option value="executor">Executors</option>
                <option value="applicant">Applicants</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
            </select>
            <a href="{{ route('provincial.executors') }}" class="btn btn-sm btn-outline-primary ms-2">Manage Team</a>
            <a href="{{ route('provincial.createExecutor') }}" class="btn btn-sm btn-primary ms-2">Add Member</a>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle ms-2" data-widget="team-overview" title="Minimize">−</button>
        </div>
    </div>
    <div class="card-body widget-content">
        {{-- Team Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Total Members</small>
                        <h3 class="mb-0">{{ $teamStats['total_members'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Active Members</small>
                        <h3 class="mb-0">{{ $teamStats['active_members'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Total Projects</small>
                        <h3 class="mb-0">{{ $teamStats['total_projects'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Total Reports</small>
                        <h3 class="mb-0">{{ $teamStats['total_reports'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Team Members List with Performance Indicators --}}
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" id="teamMembersTable">
                <thead>
                    <tr>
                        <th style="min-width: 150px; max-width: 200px;">Name</th>
                        <th style="min-width: 70px; width: 80px;">Role</th>
                        <th style="min-width: 100px; max-width: 130px;">Center</th>
                        <th style="min-width: 70px; width: 80px;">Status</th>
                        <th class="text-center" style="min-width: 60px; width: 70px;">Projects</th>
                        <th class="text-center" style="min-width: 60px; width: 70px;">Reports</th>
                        <th class="text-center" style="min-width: 90px; width: 100px;">Approval Rate</th>
                        <th style="min-width: 90px; max-width: 120px;">Performance</th>
                        <th style="min-width: 100px; width: 120px;">Last Activity</th>
                        <th style="min-width: 160px; width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($teamMembers ?? collect())->take(15) as $member)
                        @php
                            $lastActivity = $member->updated_at ? $member->updated_at->diffForHumans() : 'Never';
                            $statusClass = $member->status === 'active' ? 'success' : 'secondary';
                            
                            // Calculate performance indicators
                            $memberReports = DPReport::where('user_id', $member->id)->get();
                            $totalReports = $memberReports->count();
                            $approvedReports = $memberReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)->count();
                            $approvalRate = $totalReports > 0 ? (($approvedReports / $totalReports) * 100) : 0;
                            
                            // Performance score (based on projects, reports, approval rate)
                            $performanceScore = 0;
                            if (($member->projects_count ?? 0) > 0) $performanceScore += 30;
                            if (($member->reports_count ?? 0) > 0) $performanceScore += 20;
                            if ($approvalRate >= 80) $performanceScore += 50;
                            elseif ($approvalRate >= 60) $performanceScore += 30;
                            elseif ($approvalRate > 0) $performanceScore += 10;
                            
                            $performanceLabel = $performanceScore >= 70 ? 'Excellent' : ($performanceScore >= 40 ? 'Good' : 'Needs Improvement');
                            $performanceShort = $performanceScore >= 70 ? 'Excellent' : ($performanceScore >= 40 ? 'Good' : 'Needs Improve');
                        @endphp
                        <tr class="team-member-row" 
                            data-role="{{ $member->role }}"
                            data-status="{{ $member->status ?? 'inactive' }}"
                            data-performance="{{ $performanceScore }}">
                            <td class="py-2">
                                <div>
                                    <strong style="font-size: 0.875rem;">{{ $member->name }}</strong>
                                    @if($member->email)
                                        <br><small class="text-muted" style="font-size: 0.7rem; display: block; word-break: break-word; line-height: 1.2;">{{ Str::limit($member->email, 25) }}</small>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2">
                                <span class="badge bg-{{ $member->role === 'executor' ? 'primary' : 'info' }}" style="font-size: 0.7rem;">
                                    {{ ucfirst($member->role) }}
                                </span>
                            </td>
                            <td class="py-2">
                                <small style="font-size: 0.8rem;">{{ Str::limit($member->center ?? 'N/A', 15) }}</small>
                            </td>
                            <td class="py-2">
                                <span class="badge bg-{{ $statusClass }}" style="font-size: 0.7rem;">
                                    {{ ucfirst($member->status ?? 'inactive') }}
                                </span>
                            </td>
                            <td class="py-2 text-center">
                                {{ $member->projects_count ?? 0 }}
                            </td>
                            <td class="py-2 text-center">
                                {{ $member->reports_count ?? 0 }}
                            </td>
                            <td class="py-2 text-center">
                                @if($totalReports > 0)
                                    <small>{{ format_indian_percentage($approvalRate, 1) }}</small>
                                @else
                                    <small class="text-muted">N/A</small>
                                @endif
                            </td>
                            <td class="py-2">
                                <small style="font-size: 0.75rem; word-wrap: break-word; line-height: 1.2;" 
                                       data-bs-toggle="tooltip" 
                                       title="{{ $performanceLabel }}">
                                    {{ $performanceShort }}
                                </small>
                            </td>
                            <td class="py-2">
                                <small class="text-muted" style="font-size: 0.75rem;">{{ $lastActivity }}</small>
                            </td>
                            <td class="py-2">
                                <div class="d-flex gap-1 flex-wrap align-items-center">
                                    <a href="{{ route('provincial.editExecutor', $member->id) }}" 
                                       class="btn btn-sm btn-primary" style="padding: 0.15rem 0.4rem; font-size: 0.7rem; line-height: 1.2;">
                                        Edit
                                    </a>
                                    <a href="{{ route('provincial.projects.list') }}?user_id={{ $member->id }}" 
                                       class="btn btn-sm btn-info" style="padding: 0.15rem 0.4rem; font-size: 0.7rem; line-height: 1.2;">
                                        Projects
                                    </a>
                                    <a href="{{ route('provincial.report.list') }}?user_id={{ $member->id }}" 
                                       class="btn btn-sm btn-warning" style="padding: 0.15rem 0.4rem; font-size: 0.7rem; line-height: 1.2;">
                                        Reports
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <p class="text-muted">No team members found</p>
                                <a href="{{ route('provincial.createExecutor') }}" class="btn btn-sm btn-primary">Add Team Member</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(($teamMembers ?? collect())->count() > 15)
            <div class="text-center mt-3">
                <a href="{{ route('provincial.executors') }}" class="btn btn-sm btn-primary">
                    View All {{ $teamMembers->count() }} Team Members
                </a>
            </div>
        @endif

        {{-- Additional Stats --}}
        <div class="row mt-4 pt-3 border-top">
            <div class="col-md-6">
                <small class="text-muted">Average Projects per Member:</small>
                <strong>{{ $teamStats['avg_projects_per_member'] ?? 0 }}</strong>
            </div>
            <div class="col-md-6">
                <small class="text-muted">Average Reports per Member:</small>
                <strong>{{ $teamStats['avg_reports_per_member'] ?? 0 }}</strong>
            </div>
        </div>
    </div>
</div>

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

    // Team overview filter
    const teamFilter = document.getElementById('teamOverviewFilter');
    if (teamFilter) {
        teamFilter.addEventListener('change', function() {
            const filterValue = this.value;
            document.querySelectorAll('.team-member-row').forEach(row => {
                if (!filterValue) {
                    row.style.display = '';
                } else if (filterValue === 'executor' || filterValue === 'applicant') {
                    row.style.display = row.dataset.role === filterValue ? '' : 'none';
                } else if (filterValue === 'active' || filterValue === 'inactive') {
                    row.style.display = row.dataset.status === filterValue ? '' : 'none';
                }
            });
        });
    }
});
</script>

<style>
/* Compact table styling - optimized to prevent horizontal scroll */
#teamMembersTable {
    font-size: 0.875rem;
    table-layout: auto;
    width: 100%;
    margin-bottom: 0;
}

#teamMembersTable th {
    padding: 0.45rem 0.3rem;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

#teamMembersTable td {
    padding: 0.25rem 0.3rem;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Compact name column */
#teamMembersTable td:first-child {
    padding-left: 0.5rem;
    padding-right: 0.4rem;
}

/* Compact numeric columns - center aligned, just numbers */
#teamMembersTable td:nth-child(5),
#teamMembersTable td:nth-child(6) {
    text-align: center;
    font-weight: 500;
    font-size: 0.875rem;
}

/* Approval Rate column */
#teamMembersTable td:nth-child(7) {
    text-align: center;
    font-size: 0.8rem;
}

/* Performance column - plain text, allow wrapping */
#teamMembersTable td:nth-child(8) {
    font-size: 0.75rem;
    line-height: 1.3;
    word-break: break-word;
}

/* Last Activity column */
#teamMembersTable td:nth-child(9) {
    font-size: 0.75rem;
    white-space: nowrap;
}

/* Action buttons compact */
#teamMembersTable .d-flex.gap-1 {
    gap: 0.25rem !important;
    flex-wrap: wrap;
}

#teamMembersTable .d-flex.gap-1 .btn {
    white-space: nowrap;
    padding: 0.15rem 0.4rem;
    font-size: 0.7rem;
    line-height: 1.2;
    border-radius: 3px;
    min-width: auto;
}

/* Remove excessive spacing in rows */
#teamMembersTable tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    height: auto;
}

#teamMembersTable tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.03);
}

/* Compact badges - smaller */
#teamMembersTable .badge {
    font-size: 0.65rem;
    padding: 0.15rem 0.35rem;
    font-weight: 500;
}

/* Email text styling - more compact */
#teamMembersTable td:first-child small {
    display: block;
    margin-top: 0.05rem;
    line-height: 1.1;
}

/* Center and Role columns - compact */
#teamMembersTable td:nth-child(2),
#teamMembersTable td:nth-child(3),
#teamMembersTable td:nth-child(4) {
    font-size: 0.75rem;
}

/* Ensure table container doesn't cause overflow */
.table-responsive {
    max-width: 100%;
}
</style>
@endpush
