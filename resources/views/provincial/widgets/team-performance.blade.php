@php
    use App\Models\Reports\Monthly\DPReport;
    use App\Constants\ProjectStatus;
@endphp
{{-- Team Performance Summary Widget --}}
<div class="card mb-4 widget-card" data-widget-id="team-performance">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="trending-up" class="me-2"></i>Team Performance Summary
        </h5>
        <div class="d-flex align-items-center">
            <div class="btn-group btn-group-sm me-2" role="group">
                <button type="button" class="btn btn-outline-secondary" id="timeRange7Days">7 Days</button>
                <button type="button" class="btn btn-outline-secondary" id="timeRange30Days">30 Days</button>
                <button type="button" class="btn btn-outline-secondary active" id="timeRangeAll">All Time</button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="team-performance" title="Minimize">
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        @php
            $performanceMetrics = $performanceMetrics ?? [
                'total_projects' => 0,
                'total_reports' => 0,
                'budget_utilization' => 0,
                'approval_rate' => 0,
                'total_budget' => 0,
                'total_expenses' => 0,
                'projects_by_status' => [],
                'reports_by_status' => [],
            ];
            $chartData = $chartData ?? [
                'projects_by_status' => [],
                'reports_by_status' => [],
                'budget_by_project_type' => [],
                'budget_by_center' => [],
            ];
            $centerPerformance = $centerPerformance ?? [];
        @endphp

        {{-- Performance Metrics Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Total Projects</small>
                        <h3 class="mb-0">{{ $performanceMetrics['total_projects'] ?? 0 }}</h3>
                        <small class="d-block mt-1">
                            @if(isset($performanceMetrics['projects_by_status']) && count($performanceMetrics['projects_by_status']) > 0)
                                Approved: {{ $performanceMetrics['projects_by_status'][ProjectStatus::APPROVED_BY_COORDINATOR] ?? 0 }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Total Reports</small>
                        <h3 class="mb-0">{{ $performanceMetrics['total_reports'] }}</h3>
                        <small class="d-block mt-1">
                            @if($performanceMetrics['reports_by_status'])
                                Approved: {{ $performanceMetrics['reports_by_status'][DPReport::STATUS_APPROVED_BY_COORDINATOR] ?? 0 }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Budget Utilization</small>
                        <h3 class="mb-0">{{ format_indian_percentage($performanceMetrics['budget_utilization'], 1) }}</h3>
                        <small class="d-block mt-1">
                            {{ format_indian_currency($performanceMetrics['total_expenses'] ?? 0) }} /
                            {{ format_indian_currency($performanceMetrics['total_budget'] ?? 0) }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Approval Rate</small>
                        <h3 class="mb-0">{{ format_indian_percentage($performanceMetrics['approval_rate'], 1) }}</h3>
                        <small class="d-block mt-1">
                            {{ $performanceMetrics['approved_reports'] ?? 0 }} / {{ $performanceMetrics['total_submitted_reports'] ?? 0 }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="row mb-4">
            {{-- Projects by Status Chart --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Projects by Status</h6>
                    </div>
                    <div class="card-body">
                        <div id="projectsStatusChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>

            {{-- Reports by Status Chart --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Reports by Status</h6>
                    </div>
                    <div class="card-body">
                        <div id="reportsStatusChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget Distribution Charts --}}
        <div class="row mb-4">
            {{-- Budget by Project Type --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Budget by Project Type</h6>
                    </div>
                    <div class="card-body">
                        <div id="budgetByProjectTypeChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>

            {{-- Budget by Center --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Budget by Center</h6>
                    </div>
                    <div class="card-body">
                        <div id="budgetByCenterChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Center-Wise Breakdown Table --}}
        @if(isset($centerPerformance) && count($centerPerformance) > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Center Performance Breakdown</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Center</th>
                                <th>Projects</th>
                                <th>Budget</th>
                                <th>Expenses</th>
                                <th>Utilization %</th>
                                <th>Reports</th>
                                <th>Approval Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($centerPerformance as $center => $stats)
                            <tr>
                                <td><strong>{{ $center }}</strong></td>
                                <td>{{ $stats['projects'] ?? 0 }}</td>
                                <td>{{ format_indian_currency($stats['budget'] ?? 0) }}</td>
                                <td>{{ format_indian_currency($stats['expenses'] ?? 0) }}</td>
                                <td>
                                    @php
                                        $utilization = ($stats['budget'] ?? 0) > 0
                                            ? (($stats['expenses'] ?? 0) / ($stats['budget'] ?? 1)) * 100
                                            : 0;
                                        $utilClass = $utilization > 80 ? 'danger' : ($utilization > 60 ? 'warning' : 'success');
                                    @endphp
                                    <span class="badge bg-{{ $utilClass }}">{{ format_indian_percentage($utilization, 1) }}</span>
                                </td>
                                <td>{{ $stats['reports'] ?? 0 }}</td>
                                <td>
                                    @php
                                        $approvalRate = ($stats['total_reports'] ?? 0) > 0
                                            ? (($stats['approved_reports'] ?? 0) / ($stats['total_reports'] ?? 1)) * 100
                                            : 0;
                                    @endphp
                                    <span class="badge bg-{{ $approvalRate >= 80 ? 'success' : ($approvalRate >= 60 ? 'warning' : 'danger') }}">
                                        {{ format_indian_percentage($approvalRate, 1) }}
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
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize charts if ApexCharts is available
    if (typeof ApexCharts !== 'undefined') {
        initializeTeamPerformanceCharts();
    }

    // Time range button handlers
    document.querySelectorAll('#timeRange7Days, #timeRange30Days, #timeRangeAll').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#timeRange7Days, #timeRange30Days, #timeRangeAll').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            // TODO: Implement time range filtering
            // Time range filtering logic can be implemented here
        });
    });
});

// Dark theme colors for ApexCharts
const darkThemeColors = {
    primary: '#6571ff',
    success: '#05a34a',
    warning: '#fbbc06',
    danger: '#ff3366',
    info: '#66d1d1',
    secondary: '#6b7280',
    colors: ['#6571ff', '#05a34a', '#fbbc06', '#ff3366', '#66d1d1', '#ec4899', '#10b981', '#3b82f6']
};

// Chart instances
let projectsStatusChart = null;
let reportsStatusChart = null;
let budgetByProjectTypeChart = null;
let budgetByCenterChart = null;

function initializeTeamPerformanceCharts() {
    const chartData = @json($chartData ?? []);

    // Projects by Status Chart (Donut)
    if (document.querySelector("#projectsStatusChart") && chartData.projects_by_status) {
        const projectsStatus = chartData.projects_by_status;
        const statusLabels = Object.keys(projectsStatus).map(status => {
            const labels = {
                'draft': 'Draft',
                'submitted_to_provincial': 'Submitted',
                'reverted_by_provincial': 'Reverted (Provincial)',
                'forwarded_to_coordinator': 'Forwarded',
                'reverted_by_coordinator': 'Reverted (Coordinator)',
                'approved_by_coordinator': 'Approved',
                'rejected_by_coordinator': 'Rejected'
            };
            return labels[$status] ?? status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        });

        projectsStatusChart = new ApexCharts(document.querySelector("#projectsStatusChart"), {
            series: Object.values(projectsStatus),
            chart: {
                type: 'donut',
                height: 300,
                foreColor: '#d0d6e1'
            },
            labels: statusLabels,
            colors: darkThemeColors.colors,
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
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Projects',
                                color: '#d0d6e1',
                                formatter: function() {
                                    return Object.values(projectsStatus).reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            }
        });
        projectsStatusChart.render();
    }

    // Reports by Status Chart (Donut)
    if (document.querySelector("#reportsStatusChart") && chartData.reports_by_status) {
        const reportsStatus = chartData.reports_by_status;
        const reportStatusLabels = Object.keys(reportsStatus).map(status => {
            const labels = {
                'draft': 'Draft',
                'submitted_to_provincial': 'Submitted',
                'reverted_by_provincial': 'Reverted (Provincial)',
                'forwarded_to_coordinator': 'Forwarded',
                'reverted_by_coordinator': 'Reverted (Coordinator)',
                'approved_by_coordinator': 'Approved',
                'rejected_by_coordinator': 'Rejected'
            };
            return labels[$status] ?? status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        });

        reportsStatusChart = new ApexCharts(document.querySelector("#reportsStatusChart"), {
            series: Object.values(reportsStatus),
            chart: {
                type: 'donut',
                height: 300,
                foreColor: '#d0d6e1'
            },
            labels: reportStatusLabels,
            colors: darkThemeColors.colors,
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
                        return val + ' report' + (val !== 1 ? 's' : '');
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Reports',
                                color: '#d0d6e1',
                                formatter: function() {
                                    return Object.values(reportsStatus).reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            }
        });
        reportsStatusChart.render();
    }

    // Budget by Project Type Chart (Bar)
    if (document.querySelector("#budgetByProjectTypeChart") && chartData.budget_by_project_type) {
        const budgetByType = chartData.budget_by_project_type;

        budgetByProjectTypeChart = new ApexCharts(document.querySelector("#budgetByProjectTypeChart"), {
            series: [{
                name: 'Budget',
                data: Object.values(budgetByType)
            }],
            chart: {
                type: 'bar',
                height: 300,
                foreColor: '#d0d6e1',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return 'Rs. ' + (val / 1000).toFixed(0) + 'K';
                }
            },
            xaxis: {
                categories: Object.keys(budgetByType),
                labels: {
                    style: {
                        colors: '#d0d6e1'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#d0d6e1'
                    },
                    formatter: function(val) {
                        return 'Rs. ' + (val / 1000).toFixed(0) + 'K';
                    }
                }
            },
            colors: [darkThemeColors.primary],
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val) {
                        return 'Rs. ' + val.toLocaleString('en-IN');
                    }
                }
            }
        });
        budgetByProjectTypeChart.render();
    }

    // Budget by Center Chart (Bar)
    if (document.querySelector("#budgetByCenterChart") && chartData.budget_by_center) {
        const budgetByCenter = chartData.budget_by_center;

        budgetByCenterChart = new ApexCharts(document.querySelector("#budgetByCenterChart"), {
            series: [{
                name: 'Budget',
                data: Object.values(budgetByCenter)
            }],
            chart: {
                type: 'bar',
                height: 300,
                foreColor: '#d0d6e1',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return 'Rs. ' + (val / 1000).toFixed(0) + 'K';
                }
            },
            xaxis: {
                categories: Object.keys(budgetByCenter).map(c => c.length > 15 ? c.substring(0, 15) + '...' : c),
                labels: {
                    style: {
                        colors: '#d0d6e1'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#d0d6e1'
                    },
                    formatter: function(val) {
                        return 'Rs. ' + (val / 1000).toFixed(0) + 'K';
                    }
                }
            },
            colors: [darkThemeColors.info],
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val) {
                        return 'Rs. ' + val.toLocaleString('en-IN');
                    }
                }
            }
        });
        budgetByCenterChart.render();
    }
}
</script>
@endpush
