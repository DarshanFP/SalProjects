{{-- System Performance Summary Widget --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Performance Summary</h5>
        <button type="button" class="btn btn-sm btn-secondary" onclick="refreshSystemPerformance()">Refresh</button>
    </div>
    <div class="card-body">
        @if(isset($systemPerformanceData))
            {{-- Key Metrics Cards --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body p-3">
                            <small class="d-block mb-1">Total Projects</small>
                            <h3 class="mb-0">{{ format_indian_integer($systemPerformanceData['total_projects'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body p-3">
                            <small class="d-block mb-1">Total Reports</small>
                            <h3 class="mb-0">{{ format_indian_integer($systemPerformanceData['total_reports'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body p-3">
                            <small class="d-block mb-1">Budget Utilization</small>
                            <h3 class="mb-0">{{ format_indian_percentage($systemPerformanceData['budget_utilization'] ?? 0, 1) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body p-3">
                            <small class="d-block mb-1">Approval Rate</small>
                            <h3 class="mb-0">{{ format_indian_percentage($systemPerformanceData['approval_rate'] ?? 0, 1) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Budget Summary --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Total Budget</small>
                            <h5 class="mb-0">{{ format_indian_currency($systemPerformanceData['total_budget'] ?? 0, 2) }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Total Expenses</small>
                            <h5 class="mb-0">{{ format_indian_currency($systemPerformanceData['total_expenses'] ?? 0, 2) }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <small class="text-muted d-block">Remaining Budget</small>
                            <h5 class="mb-0">{{ format_indian_currency($systemPerformanceData['total_remaining'] ?? 0, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Distribution Charts --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Projects by Status</h6>
                        </div>
                        <div class="card-body">
                            <div id="projectsStatusChart" style="min-height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Reports by Status</h6>
                        </div>
                        <div class="card-body">
                            <div id="reportsStatusChart" style="min-height: 250px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Province Performance --}}
            @if(isset($systemPerformanceData['province_metrics']) && count($systemPerformanceData['province_metrics']) > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Province Performance</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th>Province</th>
                                        <th>Projects</th>
                                        <th>Reports</th>
                                        <th>Budget</th>
                                        <th>Expenses</th>
                                        <th>Utilization</th>
                                        <th>Approval Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($systemPerformanceData['province_metrics'] as $province => $metrics)
                                        <tr>
                                            <td><strong>{{ $province }}</strong></td>
                                            <td>{{ format_indian_integer($metrics['projects'] ?? 0) }}</td>
                                            <td>{{ format_indian_integer($metrics['reports'] ?? 0) }}</td>
                                            <td>{{ format_indian_currency($metrics['budget'] ?? 0, 2) }}</td>
                                            <td>{{ format_indian_currency($metrics['expenses'] ?? 0, 2) }}</td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar {{ $metrics['utilization'] > 80 ? 'bg-danger' : ($metrics['utilization'] > 60 ? 'bg-warning' : 'bg-success') }}" 
                                                         role="progressbar" 
                                                         style="width: {{ min($metrics['utilization'], 100) }}%"
                                                         aria-valuenow="{{ $metrics['utilization'] }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        {{ format_indian_percentage($metrics['utilization'], 1) }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $metrics['approval_rate'] > 80 ? 'success' : ($metrics['approval_rate'] > 60 ? 'warning' : 'danger') }}">
                                                    {{ format_indian_percentage($metrics['approval_rate'], 1) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Active Users --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body p-3 text-center">
                            <h6 class="text-muted mb-2">Active Users in System</h6>
                            <h3 class="mb-0">{{ format_indian_integer($systemPerformanceData['active_users'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-4">
                <p class="text-muted">No system performance data available</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function refreshSystemPerformance() {
    location.reload();
}

document.addEventListener('DOMContentLoaded', function() {
    @if(isset($systemPerformanceData))
        // Projects by Status Chart
        const projectsStatusData = @json($systemPerformanceData['projects_by_status'] ?? []);
        if (Object.keys(projectsStatusData).length > 0 && typeof ApexCharts !== 'undefined') {
            const projectsStatusChart = new ApexCharts(document.querySelector("#projectsStatusChart"), {
                series: Object.values(projectsStatusData),
                chart: {
                    type: 'donut',
                    height: 250
                },
                labels: Object.keys(projectsStatusData).map(status => status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())),
                colors: ['#667eea', '#11998e', '#f59e0b', '#ef4444', '#8b5cf6'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + ' projects';
                        }
                    }
                }
            });
            projectsStatusChart.render();
        }

        // Reports by Status Chart
        const reportsStatusData = @json($systemPerformanceData['reports_by_status'] ?? []);
        if (Object.keys(reportsStatusData).length > 0 && typeof ApexCharts !== 'undefined') {
            const reportsStatusChart = new ApexCharts(document.querySelector("#reportsStatusChart"), {
                series: Object.values(reportsStatusData),
                chart: {
                    type: 'donut',
                    height: 250
                },
                labels: Object.keys(reportsStatusData).map(status => status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())),
                colors: ['#667eea', '#11998e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + ' reports';
                        }
                    }
                }
            });
            reportsStatusChart.render();
        }
    @endif
});
</script>
@endpush