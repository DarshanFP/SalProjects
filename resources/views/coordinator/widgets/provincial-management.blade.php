@php
    $managementData = $provincialManagementData ?? [];
    $provincials = $managementData['provincials'] ?? collect();
    $summary = $managementData['summary'] ?? [];
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Provincial Management</h5>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-primary" onclick="viewAllProvincials()">
                View All
            </button>
            <button type="button" class="btn btn-secondary" onclick="exportProvincials()">
                Export
            </button>
        </div>
    </div>
    <div class="card-body">
        @if(empty($provincials) || $provincials->count() === 0)
            {{-- Empty State --}}
            <div class="text-center py-5">
                <h5 class="text-muted">No Provincials Found</h5>
                <p class="text-muted">There are no provincials in the system yet.</p>
            </div>
        @else
            {{-- Summary Cards --}}
            <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Provincials</h6>
                        <h4 class="mb-0">{{ $summary['total'] ?? 0 }}</h4>
                        <small>{{ $summary['active'] ?? 0 }} active</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Team Members</h6>
                        <h4 class="mb-0">{{ $summary['total_team_members'] ?? 0 }}</h4>
                        <small>Across all provincials</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Avg Approval Rate</h6>
                        <h4 class="mb-0">{{ format_indian_percentage($summary['avg_approval_rate'] ?? 0, 1) }}</h4>
                        <small>System-wide average</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Avg Performance</h6>
                        <h4 class="mb-0">{{ format_indian($summary['avg_performance_score'] ?? 0, 1) }}</h4>
                        <small>Out of 100</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Provincial Cards/Table --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Provincial Performance Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th>Rank</th>
                                        <th>Provincial</th>
                                        <th>Province</th>
                                        <th>Center</th>
                                        <th>Status</th>
                                        <th>Team Members</th>
                                        <th>Projects</th>
                                        <th>Reports</th>
                                        <th>Pending</th>
                                        <th>Approved</th>
                                        <th>Budget</th>
                                        <th>Expenses</th>
                                        <th>Utilization</th>
                                        <th>Approval Rate</th>
                                        <th>Last Activity</th>
                                        <th>Performance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($provincials as $provincial)
                                        @php
                                            $rank = $loop->iteration;
                                            $performanceLevel = $provincial['performance_level'] ?? 'poor';
                                            $performanceColor = match($performanceLevel) {
                                                'excellent' => 'success',
                                                'good' => 'info',
                                                'fair' => 'warning',
                                                default => 'danger',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($rank <= 5)
                                                    <span class="badge badge-{{ $rank <= 3 ? 'warning' : 'info' }}">
                                                        #{{ $rank }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">#{{ $rank }}</span>
                                                @endif
                                            </td>
                                            <td><strong>{{ $provincial['name'] }}</strong></td>
                                            <td><span class="badge badge-secondary">{{ $provincial['province'] }}</span></td>
                                            <td><small>{{ $provincial['center'] }}</small></td>
                                            <td>
                                                <span class="badge badge-{{ $provincial['status'] === 'active' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($provincial['status']) }}
                                                </span>
                                            </td>
                                            <td><span class="badge badge-info">{{ $provincial['team_members_count'] }}</span></td>
                                            <td>
                                                <span class="badge badge-primary">{{ $provincial['projects_count'] }}</span>
                                                <small class="text-muted">({{ $provincial['approved_projects_count'] }} approved)</small>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ $provincial['reports_count'] }}</span>
                                            </td>
                                            <td>
                                                @if($provincial['pending_reports_count'] > 0)
                                                    <span class="badge badge-warning">{{ $provincial['pending_reports_count'] }}</span>
                                                @else
                                                    <span class="badge badge-success">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-success">{{ $provincial['approved_reports_count'] }}</span>
                                            </td>
                                            <td><small>{{ format_indian_currency($provincial['budget'], 2) }}</small></td>
                                            <td><small>{{ format_indian_currency($provincial['expenses'], 2) }}</small></td>
                                            <td>
                                                <div class="progress" style="height: 20px; width: 80px;">
                                                    <div class="progress-bar {{ $provincial['utilization'] >= 90 ? 'bg-danger' : ($provincial['utilization'] >= 75 ? 'bg-warning' : 'bg-success') }}"
                                                         style="width: {{ min($provincial['utilization'], 100) }}%"
                                                         title="{{ format_indian_percentage($provincial['utilization'], 1) }}">
                                                        {{ format_indian_percentage($provincial['utilization'], 1) }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px; width: 80px;">
                                                    <div class="progress-bar {{ $provincial['approval_rate'] >= 80 ? 'bg-success' : ($provincial['approval_rate'] >= 60 ? 'bg-warning' : 'bg-danger') }}"
                                                         style="width: {{ min($provincial['approval_rate'], 100) }}%"
                                                         title="{{ format_indian_percentage($provincial['approval_rate'], 1) }}">
                                                        {{ format_indian_percentage($provincial['approval_rate'], 1) }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($provincial['last_activity'])
                                                    <small>{{ \Illuminate\Support\Carbon::parse($provincial['last_activity'])->diffForHumans() }}</small>
                                                    <br>
                                                    <small class="text-muted">({{ $provincial['days_since_activity'] }} days ago)</small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $performanceColor }}">
                                                    {{ format_indian_integer($provincial['performance_score']) }} - {{ ucfirst($performanceLevel) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('coordinator.provincials') }}"
                                                       class="btn btn-primary btn-sm"
                                                       title="View Details">
                                                        View
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="17" class="text-center text-muted py-4">No provincials found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // View All Provincials
    window.viewAllProvincials = function() {
        window.location.href = '{{ route("coordinator.provincials") }}';
    };

    // Export Provincials
    window.exportProvincials = function() {
        alert('Export functionality will be implemented soon.');
    };
});
</script>
@endpush
