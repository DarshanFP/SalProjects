@php
    $performanceData = $systemPerformanceData ?? [];
    $coordinatorHierarchy = $performanceData['coordinator_hierarchy'] ?? [];
    $directTeam = $performanceData['direct_team'] ?? [];
    $combined = $performanceData['combined'] ?? [];
@endphp

{{-- System Performance Widget --}}
<div class="card mb-4 widget-card" data-widget-id="system-performance">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="bar-chart-2" class="me-2"></i>System Performance
        </h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="system-performance" title="Minimize">
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if(empty($performanceData))
            <div class="text-center py-4">
                <i data-feather="bar-chart-2" class="text-muted" style="width: 48px; height: 48px;"></i>
                <p class="text-muted mt-3">No performance data available</p>
            </div>
        @else
            {{-- Overall Performance Metrics Cards --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Overall Approval Rate</small>
                            <h3 class="mb-0">{{ format_indian_percentage($combined['approval_rate'] ?? 0, 1) }}</h3>
                            <small class="d-block mt-1">System-wide</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Avg Processing Time</small>
                            <h3 class="mb-0">{{ $combined['avg_processing_time'] ?? 0 }} days</h3>
                            <small class="d-block mt-1">For approved reports</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Project Completion Rate</small>
                            <h3 class="mb-0">{{ format_indian_percentage($combined['project_completion_rate'] ?? 0, 1) }}</h3>
                            <small class="d-block mt-1">Approved projects</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white" style="height: 120px;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <small class="d-block">Budget Utilization</small>
                            <h3 class="mb-0">{{ format_indian_percentage($combined['budget_utilization'] ?? 0, 1) }}</h3>
                            <small class="d-block mt-1">System-wide</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Context Comparison Cards --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i data-feather="users" class="me-2" style="width: 16px; height: 16px;"></i>
                                Coordinator Hierarchy Performance
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Total Projects</small>
                                    <h5 class="mb-0">{{ format_indian_integer($coordinatorHierarchy['total_projects'] ?? 0) }}</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Total Reports</small>
                                    <h5 class="mb-0">{{ format_indian_integer($coordinatorHierarchy['total_reports'] ?? 0) }}</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Approval Rate</small>
                                    <h5 class="mb-0 text-success">{{ format_indian_percentage($coordinatorHierarchy['approval_rate'] ?? 0, 1) }}</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Avg Processing Time</small>
                                    <h5 class="mb-0">{{ $coordinatorHierarchy['avg_processing_time'] ?? 0 }} days</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Budget Utilization</small>
                                    <h5 class="mb-0">{{ format_indian_percentage($coordinatorHierarchy['budget_utilization'] ?? 0, 1) }}</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Completion Rate</small>
                                    <h5 class="mb-0">{{ format_indian_percentage($coordinatorHierarchy['project_completion_rate'] ?? 0, 1) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i data-feather="user" class="me-2" style="width: 16px; height: 16px;"></i>
                                Direct Team Performance
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Total Projects</small>
                                    <h5 class="mb-0">{{ format_indian_integer($directTeam['total_projects'] ?? 0) }}</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Total Reports</small>
                                    <h5 class="mb-0">{{ format_indian_integer($directTeam['total_reports'] ?? 0) }}</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Approval Rate</small>
                                    <h5 class="mb-0 text-success">{{ format_indian_percentage($directTeam['approval_rate'] ?? 0, 1) }}</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Avg Processing Time</small>
                                    <h5 class="mb-0">{{ $directTeam['avg_processing_time'] ?? 0 }} days</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Budget Utilization</small>
                                    <h5 class="mb-0">{{ format_indian_percentage($directTeam['budget_utilization'] ?? 0, 1) }}</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Completion Rate</small>
                                    <h5 class="mb-0">{{ format_indian_percentage($directTeam['project_completion_rate'] ?? 0, 1) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Metrics Comparison Chart --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i data-feather="bar-chart-2" class="me-2" style="width: 16px; height: 16px;"></i>
                                Performance Metrics Comparison (Coordinator Hierarchy vs Direct Team)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="performanceMetricsComparisonChart" style="min-height: 350px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Distribution Charts --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Projects by Status (Combined)</h6>
                        </div>
                        <div class="card-body">
                            @if(!empty($combined['projects_by_status']))
                                <div id="projectsStatusChart" style="min-height: 250px;"></div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i data-feather="pie-chart" style="width: 32px; height: 32px; opacity: 0.3;"></i>
                                    <p class="mt-2 mb-0">No project status data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Reports by Status (Combined)</h6>
                        </div>
                        <div class="card-body">
                            @if(!empty($combined['reports_by_status']))
                                <div id="reportsStatusChart" style="min-height: 250px;"></div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i data-feather="pie-chart" style="width: 32px; height: 32px; opacity: 0.3;"></i>
                                    <p class="mt-2 mb-0">No report status data available</p>
                                </div>
                            @endif
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
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    @if(!empty($combined['projects_by_status']))
        // Projects by Status Chart
        const projectsStatusData = @json($combined['projects_by_status']);
        if (Object.keys(projectsStatusData).length > 0 && typeof ApexCharts !== 'undefined') {
            const projectsStatusChart = new ApexCharts(document.querySelector("#projectsStatusChart"), {
                series: Object.values(projectsStatusData),
                chart: {
                    type: 'donut',
                    height: 250
                },
                labels: Object.keys(projectsStatusData).map(status => status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())),
                colors: ['#667eea', '#11998e', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'],
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
    @endif

    @if(!empty($combined['reports_by_status']))
        // Reports by Status Chart
        const reportsStatusData = @json($combined['reports_by_status']);
        if (Object.keys(reportsStatusData).length > 0 && typeof ApexCharts !== 'undefined') {
            const reportsStatusChart = new ApexCharts(document.querySelector("#reportsStatusChart"), {
                series: Object.values(reportsStatusData),
                chart: {
                    type: 'donut',
                    height: 250
                },
                labels: Object.keys(reportsStatusData).map(status => status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())),
                colors: ['#667eea', '#11998e', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#ec4899'],
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

    // Performance Metrics Comparison Chart
    @php
        $coordinatorHierarchyMetrics = $coordinatorHierarchy ?? [];
        $directTeamMetrics = $directTeam ?? [];
    @endphp
    @if(!empty($coordinatorHierarchyMetrics) && !empty($directTeamMetrics))
        const performanceMetricsElement = document.querySelector("#performanceMetricsComparisonChart");
        if (performanceMetricsElement && typeof ApexCharts !== 'undefined') {
            const coordinatorHierarchyData = @json($coordinatorHierarchyMetrics);
            const directTeamData = @json($directTeamMetrics);

            const performanceMetricsChart = new ApexCharts(performanceMetricsElement, {
                series: [
                    {
                        name: 'Coordinator Hierarchy',
                        data: [
                            coordinatorHierarchyData.approval_rate ?? 0,
                            coordinatorHierarchyData.project_completion_rate ?? 0,
                            coordinatorHierarchyData.budget_utilization ?? 0,
                            Math.min((coordinatorHierarchyData.avg_processing_time ?? 0) * 10, 100), // Normalize to 0-100
                        ]
                    },
                    {
                        name: 'Direct Team',
                        data: [
                            directTeamData.approval_rate ?? 0,
                            directTeamData.project_completion_rate ?? 0,
                            directTeamData.budget_utilization ?? 0,
                            Math.min((directTeamData.avg_processing_time ?? 0) * 10, 100), // Normalize to 0-100
                        ]
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: true },
                    zoom: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '60%',
                        borderRadius: 4,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val.toFixed(1) + '%';
                    },
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        colors: ['#333']
                    }
                },
                xaxis: {
                    categories: ['Approval Rate', 'Project Completion Rate', 'Budget Utilization', 'Processing Time Score'],
                    labels: {
                        rotate: -45,
                        rotateAlways: true
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0) + '%';
                        }
                    },
                    max: 100,
                    min: 0
                },
                colors: ['#3b82f6', '#10b981'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val, { seriesIndex, dataPointIndex }) {
                            if (dataPointIndex === 3) {
                                // Processing time score - show actual days
                                const actualValue = seriesIndex === 0
                                    ? (coordinatorHierarchyData.avg_processing_time ?? 0)
                                    : (directTeamData.avg_processing_time ?? 0);
                                return actualValue + ' days';
                            }
                            return val.toFixed(2) + '%';
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 4
                },
                responsive: [{
                    breakpoint: 768,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '80%'
                            }
                        },
                        xaxis: {
                            labels: {
                                rotate: -45
                            }
                        }
                    }
                }]
            });
            performanceMetricsChart.render();
        }
    @endif

    // Re-initialize feather icons after charts render
    setTimeout(function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }, 500);
});
</script>
@endpush
