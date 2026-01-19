@php
    use App\Models\Reports\Monthly\DPReport;
    use App\Constants\ProjectStatus;
    use Illuminate\Support\Str;
@endphp
{{-- Team Budget Overview Widget (Enhanced) --}}
<div class="card mb-4 widget-card" data-widget-id="team-budget-overview">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="dollar-sign" class="me-2"></i>Team Budget Overview
        </h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportBudgetData()" title="Export Data">
                <i data-feather="download"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="team-budget-overview" title="Minimize">
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="card-body widget-content">
        {{-- Budget Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Total Budget</small>
                        <h3 class="mb-0">{{ format_indian_currency($budgetData['total']['budget'] ?? 0) }}</h3>
                        <small class="d-block mt-1">All Team Members</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Total Expenses</small>
                        <h3 class="mb-0">{{ format_indian_currency($budgetData['total']['expenses'] ?? 0) }}</h3>
                        <small class="d-block mt-1">From Approved Reports</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Remaining Budget</small>
                        <h3 class="mb-0">{{ format_indian_currency($budgetData['total']['remaining'] ?? 0) }}</h3>
                        <small class="d-block mt-1">{{ format_indian_percentage($budgetData['total']['remaining_percentage'] ?? 0, 1) }} Available</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body p-3">
                        <small class="d-block">Budget Utilization</small>
                        <h3 class="mb-0">{{ format_indian_percentage($budgetData['total']['utilization'] ?? 0, 1) }}</h3>
                        <div class="progress mt-2" style="height: 8px; background-color: rgba(255,255,255,0.3);">
                            <div class="progress-bar bg-white" 
                                 role="progressbar" 
                                 style="width: {{ min($budgetData['total']['utilization'] ?? 0, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget Charts Row --}}
        <div class="row mb-4">
            {{-- Budget by Project Type Chart --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Budget Distribution by Project Type</h6>
                    </div>
                    <div class="card-body">
                        <div id="budgetProjectTypeChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>

            {{-- Budget by Center Chart --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Budget Distribution by Center</h6>
                    </div>
                    <div class="card-body">
                        <div id="budgetCenterChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget by Team Member Chart --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Budget Allocation by Team Member</h6>
                    </div>
                    <div class="card-body">
                        <div id="budgetTeamMemberChart" style="min-height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expense Trends Chart --}}
        @if(isset($budgetData['trends']) && count($budgetData['trends']) > 0)
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Expense Trends Over Time</h6>
                    </div>
                    <div class="card-body">
                        <div id="expenseTrendsChart" style="min-height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Detailed Breakdown Tables --}}
        <div class="row">
            {{-- Budget by Project Type Table --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Budget by Project Type (Detailed)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Project Type</th>
                                        <th class="text-end">Budget</th>
                                        <th class="text-end">Expenses</th>
                                        <th class="text-end">Remaining</th>
                                        <th class="text-end">Utilization</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($budgetData['by_project_type'] ?? [] as $type => $data)
                                    <tr>
                                        <td><strong>{{ $type }}</strong></td>
                                        <td class="text-end">{{ format_indian_currency($data['budget'] ?? 0) }}</td>
                                        <td class="text-end">{{ format_indian_currency($data['expenses'] ?? 0) }}</td>
                                        <td class="text-end">{{ format_indian_currency($data['remaining'] ?? 0) }}</td>
                                        <td class="text-end">
                                            @php
                                                $util = ($data['budget'] ?? 0) > 0 ? (($data['expenses'] ?? 0) / ($data['budget'] ?? 1)) * 100 : 0;
                                                $utilClass = $util > 80 ? 'danger' : ($util > 60 ? 'warning' : 'success');
                                            @endphp
                                            <span class="badge bg-{{ $utilClass }}">{{ format_indian_percentage($util, 1) }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No data available</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Budget by Center Table --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Budget by Center (Detailed)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Center</th>
                                        <th class="text-end">Budget</th>
                                        <th class="text-end">Expenses</th>
                                        <th class="text-end">Remaining</th>
                                        <th class="text-end">Utilization</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($budgetData['by_center'] ?? [] as $center => $data)
                                    <tr>
                                        <td><strong>{{ $center }}</strong></td>
                                        <td class="text-end">{{ format_indian_currency($data['budget'] ?? 0) }}</td>
                                        <td class="text-end">{{ format_indian_currency($data['expenses'] ?? 0) }}</td>
                                        <td class="text-end">{{ format_indian_currency($data['remaining'] ?? 0) }}</td>
                                        <td class="text-end">
                                            @php
                                                $util = ($data['budget'] ?? 0) > 0 ? (($data['expenses'] ?? 0) / ($data['budget'] ?? 1)) * 100 : 0;
                                                $utilClass = $util > 80 ? 'danger' : ($util > 60 ? 'warning' : 'success');
                                            @endphp
                                            <span class="badge bg-{{ $utilClass }}">{{ format_indian_percentage($util, 1) }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No data available</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Projects by Budget --}}
        @if(isset($budgetData['top_projects']) && count($budgetData['top_projects']) > 0)
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Top 10 Projects by Budget</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Project Title</th>
                                        <th>Team Member</th>
                                        <th>Type</th>
                                        <th class="text-end">Budget</th>
                                        <th class="text-end">Expenses</th>
                                        <th class="text-end">Utilization</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($budgetData['top_projects'] as $project)
                                    <tr>
                                        <td>
                                            <a href="{{ route('provincial.projects.show', $project['project_id']) }}" 
                                               class="text-decoration-none">
                                                {{ $project['project_id'] }}
                                            </a>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($project['title'] ?? 'N/A', 40) }}</td>
                                        <td>{{ $project['team_member'] ?? 'N/A' }}</td>
                                        <td><span class="badge bg-secondary">{{ $project['type'] ?? 'N/A' }}</span></td>
                                        <td class="text-end">{{ format_indian_currency($project['budget'] ?? 0) }}</td>
                                        <td class="text-end">{{ format_indian_currency($project['expenses'] ?? 0) }}</td>
                                        <td class="text-end">
                                            @php
                                                $util = ($project['budget'] ?? 0) > 0 ? (($project['expenses'] ?? 0) / ($project['budget'] ?? 1)) * 100 : 0;
                                                $utilClass = $util > 80 ? 'danger' : ($util > 60 ? 'warning' : 'success');
                                            @endphp
                                            <span class="badge bg-{{ $utilClass }}">{{ format_indian_percentage($util, 1) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
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
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize charts if ApexCharts is available
    if (typeof ApexCharts !== 'undefined') {
        initializeBudgetOverviewCharts();
    }
});

// Dark theme colors
const darkThemeColors = {
    primary: '#6571ff',
    success: '#05a34a',
    warning: '#fbbc06',
    danger: '#ff3366',
    info: '#66d1d1',
    secondary: '#6b7280',
    colors: ['#6571ff', '#05a34a', '#fbbc06', '#ff3366', '#66d1d1', '#ec4899', '#10b981', '#3b82f6']
};

let budgetProjectTypeChart = null;
let budgetCenterChart = null;
let budgetTeamMemberChart = null;
let expenseTrendsChart = null;

function initializeBudgetOverviewCharts() {
    const budgetData = @json($budgetData ?? []);

    // Budget by Project Type (Pie Chart)
    if (document.querySelector("#budgetProjectTypeChart") && budgetData.by_project_type) {
        const typeData = budgetData.by_project_type;
        budgetProjectTypeChart = new ApexCharts(document.querySelector("#budgetProjectTypeChart"), {
            series: Object.values(typeData).map(item => item.budget ?? 0),
            chart: {
                type: 'pie',
                height: 300,
                foreColor: '#d0d6e1'
            },
            labels: Object.keys(typeData),
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
                        return 'Rs. ' + val.toLocaleString('en-IN');
                    }
                }
            },
            dataLabels: {
                formatter: function(val, opts) {
                    return opts.w.config.labels[opts.seriesIndex] + ': ' + val.toFixed(1) + '%';
                }
            }
        });
        budgetProjectTypeChart.render();
    }

    // Budget by Center (Pie Chart)
    if (document.querySelector("#budgetCenterChart") && budgetData.by_center) {
        const centerData = budgetData.by_center;
        budgetCenterChart = new ApexCharts(document.querySelector("#budgetCenterChart"), {
            series: Object.values(centerData).map(item => item.budget ?? 0),
            chart: {
                type: 'pie',
                height: 300,
                foreColor: '#d0d6e1'
            },
            labels: Object.keys(centerData),
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
                        return 'Rs. ' + val.toLocaleString('en-IN');
                    }
                }
            }
        });
        budgetCenterChart.render();
    }

    // Budget by Team Member (Bar Chart)
    if (document.querySelector("#budgetTeamMemberChart") && budgetData.by_team_member) {
        const memberData = budgetData.by_team_member;
        budgetTeamMemberChart = new ApexCharts(document.querySelector("#budgetTeamMemberChart"), {
            series: [{
                name: 'Budget',
                data: Object.values(memberData).map(item => item.budget ?? 0)
            }],
            chart: {
                type: 'bar',
                height: 350,
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
                categories: Object.keys(memberData),
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
        budgetTeamMemberChart.render();
    }

    // Expense Trends (Area Chart)
    if (document.querySelector("#expenseTrendsChart") && budgetData.trends && budgetData.trends.length > 0) {
        const trends = budgetData.trends;
        expenseTrendsChart = new ApexCharts(document.querySelector("#expenseTrendsChart"), {
            series: [{
                name: 'Expenses',
                data: trends.map(item => item.expenses ?? 0)
            }],
            chart: {
                type: 'area',
                height: 350,
                foreColor: '#d0d6e1',
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            xaxis: {
                categories: trends.map(item => item.period ?? ''),
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
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                    stops: [0, 90, 100]
                }
            },
            colors: [darkThemeColors.success],
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val) {
                        return 'Rs. ' + val.toLocaleString('en-IN');
                    }
                }
            }
        });
        expenseTrendsChart.render();
    }
}

// Export budget data
function exportBudgetData() {
    const budgetData = @json($budgetData ?? []);
    
    // Create CSV content
    let csv = 'Budget Overview Data\n\n';
    csv += 'Total Budget,Total Expenses,Remaining,Utilization%\n';
    csv += `${budgetData.total?.budget ?? 0},${budgetData.total?.expenses ?? 0},${budgetData.total?.remaining ?? 0},${budgetData.total?.utilization ?? 0}\n\n`;
    
    csv += 'Budget by Project Type\n';
    csv += 'Project Type,Budget,Expenses,Remaining,Utilization%\n';
    if (budgetData.by_project_type) {
        Object.entries(budgetData.by_project_type).forEach(([type, data]) => {
            const util = (data.budget ?? 0) > 0 ? ((data.expenses ?? 0) / (data.budget ?? 1)) * 100 : 0;
            csv += `${type},${data.budget ?? 0},${data.expenses ?? 0},${data.remaining ?? 0},${util.toFixed(1)}\n`;
        });
    }
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'budget-overview-' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
@endpush
