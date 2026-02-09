@php
    $comparisonData = $contextComparisonData ?? [];
    $coordinatorHierarchy = $comparisonData['coordinator_hierarchy'] ?? [];
    $directTeam = $comparisonData['direct_team'] ?? [];
@endphp

{{-- Context Comparison Widget --}}
<div class="card mb-4 widget-card" data-widget-id="context-comparison">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Context Comparison</h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="context-comparison" title="Minimize">−</button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if(empty($comparisonData))
            <div class="text-center py-4">
                <p class="text-muted">No comparison data available</p>
            </div>
        @else
            {{-- Comparison Table --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 200px;">Metric</th>
                                    <th class="text-center" style="width: 300px;">
                                        <span class="badge bg-primary">Coordinator Hierarchy</span>
                                    </th>
                                    <th class="text-center" style="width: 300px;">
                                        <span class="badge bg-success">Direct Team</span>
                                    </th>
                                    <th class="text-center" style="width: 200px;">Difference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Projects Count</strong></td>
                                    <td class="text-center">{{ format_indian_integer($coordinatorHierarchy['projects_count'] ?? 0) }}</td>
                                    <td class="text-center">{{ format_indian_integer($directTeam['projects_count'] ?? 0) }}</td>
                                    <td class="text-center">
                                        @php
                                            $diff = ($coordinatorHierarchy['projects_count'] ?? 0) - ($directTeam['projects_count'] ?? 0);
                                            $diffClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                                        @endphp
                                        <span class="{{ $diffClass }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ format_indian_integer($diff) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Reports Count</strong></td>
                                    <td class="text-center">{{ format_indian_integer($coordinatorHierarchy['reports_count'] ?? 0) }}</td>
                                    <td class="text-center">{{ format_indian_integer($directTeam['reports_count'] ?? 0) }}</td>
                                    <td class="text-center">
                                        @php
                                            $diff = ($coordinatorHierarchy['reports_count'] ?? 0) - ($directTeam['reports_count'] ?? 0);
                                            $diffClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                                        @endphp
                                        <span class="{{ $diffClass }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ format_indian_integer($diff) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total Budget</strong></td>
                                    <td class="text-center">{{ format_indian_currency($coordinatorHierarchy['budget'] ?? 0, 2) }}</td>
                                    <td class="text-center">{{ format_indian_currency($directTeam['budget'] ?? 0, 2) }}</td>
                                    <td class="text-center">
                                        @php
                                            $diff = ($coordinatorHierarchy['budget'] ?? 0) - ($directTeam['budget'] ?? 0);
                                            $diffClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                                        @endphp
                                        <span class="{{ $diffClass }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ format_indian_currency($diff, 2) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total Expenses</strong></td>
                                    <td class="text-center">{{ format_indian_currency($coordinatorHierarchy['expenses'] ?? 0, 2) }}</td>
                                    <td class="text-center">{{ format_indian_currency($directTeam['expenses'] ?? 0, 2) }}</td>
                                    <td class="text-center">
                                        @php
                                            $diff = ($coordinatorHierarchy['expenses'] ?? 0) - ($directTeam['expenses'] ?? 0);
                                            $diffClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                                        @endphp
                                        <span class="{{ $diffClass }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ format_indian_currency($diff, 2) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Budget Utilization</strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ ($coordinatorHierarchy['budget_utilization'] ?? 0) > 80 ? 'danger' : (($coordinatorHierarchy['budget_utilization'] ?? 0) > 60 ? 'warning' : 'success') }}">
                                            {{ format_indian_percentage($coordinatorHierarchy['budget_utilization'] ?? 0, 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ ($directTeam['budget_utilization'] ?? 0) > 80 ? 'danger' : (($directTeam['budget_utilization'] ?? 0) > 60 ? 'warning' : 'success') }}">
                                            {{ format_indian_percentage($directTeam['budget_utilization'] ?? 0, 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $diff = ($coordinatorHierarchy['budget_utilization'] ?? 0) - ($directTeam['budget_utilization'] ?? 0);
                                            $diffClass = $diff > 0 ? 'text-warning' : ($diff < 0 ? 'text-success' : 'text-muted');
                                        @endphp
                                        <span class="{{ $diffClass }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Approval Rate</strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ ($coordinatorHierarchy['approval_rate'] ?? 0) > 80 ? 'success' : (($coordinatorHierarchy['approval_rate'] ?? 0) > 60 ? 'warning' : 'danger') }}">
                                            {{ format_indian_percentage($coordinatorHierarchy['approval_rate'] ?? 0, 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ ($directTeam['approval_rate'] ?? 0) > 80 ? 'success' : (($directTeam['approval_rate'] ?? 0) > 60 ? 'warning' : 'danger') }}">
                                            {{ format_indian_percentage($directTeam['approval_rate'] ?? 0, 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $diff = ($coordinatorHierarchy['approval_rate'] ?? 0) - ($directTeam['approval_rate'] ?? 0);
                                            $diffClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                                        @endphp
                                        <span class="{{ $diffClass }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Avg Processing Time</strong></td>
                                    <td class="text-center">{{ number_format($coordinatorHierarchy['avg_processing_time'] ?? 0, 1) }} days</td>
                                    <td class="text-center">{{ number_format($directTeam['avg_processing_time'] ?? 0, 1) }} days</td>
                                    <td class="text-center">
                                        @php
                                            $diff = ($coordinatorHierarchy['avg_processing_time'] ?? 0) - ($directTeam['avg_processing_time'] ?? 0);
                                            $diffClass = $diff < 0 ? 'text-success' : ($diff > 0 ? 'text-danger' : 'text-muted'); // Lower is better
                                        @endphp
                                        <span class="{{ $diffClass }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }} days
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Project Completion Rate</strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ ($coordinatorHierarchy['project_completion_rate'] ?? 0) > 80 ? 'success' : (($coordinatorHierarchy['project_completion_rate'] ?? 0) > 60 ? 'warning' : 'danger') }}">
                                            {{ format_indian_percentage($coordinatorHierarchy['project_completion_rate'] ?? 0, 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ ($directTeam['project_completion_rate'] ?? 0) > 80 ? 'success' : (($directTeam['project_completion_rate'] ?? 0) > 60 ? 'warning' : 'danger') }}">
                                            {{ format_indian_percentage($directTeam['project_completion_rate'] ?? 0, 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $diff = ($coordinatorHierarchy['project_completion_rate'] ?? 0) - ($directTeam['project_completion_rate'] ?? 0);
                                            $diffClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                                        @endphp
                                        <span class="{{ $diffClass }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Comparison Charts -- Enhanced --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Projects & Reports Comparison</h6>
                        </div>
                        <div class="card-body">
                            <div id="projectsReportsComparisonChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Budget & Expenses Comparison</h6>
                        </div>
                        <div class="card-body">
                            <div id="budgetExpensesComparisonChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Metrics Comparison Chart -- Enhanced --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Performance Metrics Comparison (Stacked Bar Chart)</h6>
                        </div>
                        <div class="card-body">
                            <div id="performanceMetricsComparisonChart" style="min-height: 350px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Radar Chart for Multi-Metric Comparison --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Multi-Metric Performance Radar</h6>
                        </div>
                        <div class="card-body">
                            <div id="performanceRadarChart" style="min-height: 400px;"></div>
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

    @if(!empty($comparisonData))
        // Projects & Reports Comparison Chart - Enhanced
        const projectsReportsComparisonChart = new ApexCharts(document.querySelector("#projectsReportsComparisonChart"), {
            series: [
                {
                    name: 'Projects',
                    data: [
                        {{ $coordinatorHierarchy['projects_count'] ?? 0 }},
                        {{ $directTeam['projects_count'] ?? 0 }}
                    ]
                },
                {
                    name: 'Reports',
                    data: [
                        {{ $coordinatorHierarchy['reports_count'] ?? 0 }},
                        {{ $directTeam['reports_count'] ?? 0 }}
                    ]
                }
            ],
            chart: {
                type: 'bar',
                height: 300,
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
                    return val.toLocaleString('en-IN');
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ['#333']
                }
            },
            xaxis: {
                categories: ['Coordinator Hierarchy', 'Direct Team']
            },
            colors: ['#3b82f6', '#10b981'],
            legend: {
                position: 'bottom',
                show: true
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return val.toLocaleString('en-IN');
                    }
                }
            },
            grid: {
                borderColor: '#e0e0e0',
                strokeDashArray: 4
            }
        });
        projectsReportsComparisonChart.render();

        // Budget & Expenses Comparison Chart - Enhanced Stacked
        const budgetExpensesComparisonChart = new ApexCharts(document.querySelector("#budgetExpensesComparisonChart"), {
            series: [
                {
                    name: 'Approved Expenses',
                    data: [
                        {{ ($coordinatorHierarchy['expenses'] ?? 0) / 100000 }},
                        {{ ($directTeam['expenses'] ?? 0) / 100000 }}
                    ]
                },
                {
                    name: 'Remaining Budget',
                    data: [
                        {{ (($coordinatorHierarchy['budget'] ?? 0) - ($coordinatorHierarchy['expenses'] ?? 0)) / 100000 }},
                        {{ (($directTeam['budget'] ?? 0) - ($directTeam['expenses'] ?? 0)) / 100000 }}
                    ]
                }
            ],
            chart: {
                type: 'bar',
                height: 300,
                stacked: true,
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    borderRadius: 4,
                    dataLabels: {
                        total: {
                            enabled: true,
                            offsetX: 0,
                            offsetY: 0,
                            formatter: function (val) {
                                const total = val.config.series.reduce((a, b) => a + b, 0) * 100000;
                                return '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' (Total)';
                            },
                            style: {
                                fontSize: '12px',
                                fontWeight: 600
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: ['Coordinator Hierarchy', 'Direct Team']
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return '₹' + (val * 100000).toLocaleString('en-IN', {maximumFractionDigits: 0}) + 'L';
                    }
                }
            },
            colors: ['#f59e0b', '#e5e7eb'],
            legend: {
                position: 'bottom',
                show: true
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex, dataPointIndex, w }) {
                        if (seriesIndex === undefined) {
                            const total = w.globals.initialSeries.reduce((sum, series, idx) => {
                                return sum + (series.data[dataPointIndex] || 0);
                            }, 0) * 100000;
                            return '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2}) + ' (Total Budget)';
                        }
                        return '₹' + (val * 100000).toLocaleString('en-IN', {minimumFractionDigits: 2});
                    }
                }
            },
            grid: {
                borderColor: '#e0e0e0',
                strokeDashArray: 4
            }
        });
        budgetExpensesComparisonChart.render();

        // Performance Metrics Comparison Chart - Enhanced Stacked Bar
        const performanceMetricsComparisonChart = new ApexCharts(document.querySelector("#performanceMetricsComparisonChart"), {
            series: [
                {
                    name: 'Budget Utilization %',
                    data: [
                        {{ $coordinatorHierarchy['budget_utilization'] ?? 0 }},
                        {{ $directTeam['budget_utilization'] ?? 0 }}
                    ]
                },
                {
                    name: 'Approval Rate %',
                    data: [
                        {{ $coordinatorHierarchy['approval_rate'] ?? 0 }},
                        {{ $directTeam['approval_rate'] ?? 0 }}
                    ]
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                stacked: true,
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    borderRadius: 4,
                    dataLabels: {
                        total: {
                            enabled: true,
                            offsetX: 0,
                            offsetY: 0,
                            formatter: function (val) {
                                return val.config.series.reduce((a, b) => a + b, 0).toFixed(1) + '%';
                            },
                            style: {
                                fontSize: '13px',
                                fontWeight: 600
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                },
                style: {
                    fontSize: '12px',
                    fontWeight: 500
                }
            },
            xaxis: {
                categories: ['Coordinator Hierarchy', 'Direct Team']
            },
            yaxis: {
                max: 200, // For stacked chart (100% + 100%)
                labels: {
                    formatter: function(val) {
                        return val.toFixed(0) + '%';
                    }
                }
            },
            colors: ['#8b5cf6', '#10b981'],
            legend: {
                position: 'bottom',
                show: true
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex, dataPointIndex, w }) {
                        if (seriesIndex === undefined) {
                            const total = w.globals.initialSeries.reduce((sum, series, idx) => {
                                return sum + (series.data[dataPointIndex] || 0);
                            }, 0);
                            return total.toFixed(1) + '% (Total)';
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
                    }
                }
            }]
        });
        performanceMetricsComparisonChart.render();

        // Performance Radar Chart for Multi-Metric Comparison
        const performanceRadarChart = new ApexCharts(document.querySelector("#performanceRadarChart"), {
            series: [
                {
                    name: 'Coordinator Hierarchy',
                    data: [
                        {{ $coordinatorHierarchy['approval_rate'] ?? 0 }},
                        {{ $coordinatorHierarchy['budget_utilization'] ?? 0 }},
                        Math.min(({{ $coordinatorHierarchy['avg_processing_time'] ?? 0 }} / 30) * 100, 100), // Normalize processing time (30 days = 100%)
                        100 - Math.min(({{ $coordinatorHierarchy['avg_processing_time'] ?? 0 }} / 30) * 100, 100), // Efficiency score (inverse of processing time)
                        {{ $coordinatorHierarchy['project_completion_rate'] ?? 0 }}
                    ]
                },
                {
                    name: 'Direct Team',
                    data: [
                        {{ $directTeam['approval_rate'] ?? 0 }},
                        {{ $directTeam['budget_utilization'] ?? 0 }},
                        Math.min(({{ $directTeam['avg_processing_time'] ?? 0 }} / 30) * 100, 100), // Normalize processing time
                        100 - Math.min(({{ $directTeam['avg_processing_time'] ?? 0 }} / 30) * 100, 100), // Efficiency score
                        {{ $directTeam['project_completion_rate'] ?? 0 }}
                    ]
                }
            ],
            chart: {
                type: 'radar',
                height: 400,
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            xaxis: {
                categories: ['Approval Rate', 'Budget Utilization', 'Processing Speed', 'Efficiency Score', 'Completion Rate'],
                labels: {
                    style: {
                        fontSize: '12px',
                        fontWeight: 500
                    }
                }
            },
            yaxis: {
                max: 100,
                min: 0,
                labels: {
                    formatter: function(val) {
                        return val.toFixed(0) + '%';
                    }
                }
            },
            colors: ['#3b82f6', '#10b981'],
            markers: {
                size: [6, 6],
                hover: { size: 8 }
            },
            fill: {
                opacity: [0.3, 0.3]
            },
            stroke: {
                width: [3, 3],
                curve: 'smooth'
            },
            legend: {
                position: 'bottom',
                show: true
            },
            tooltip: {
                y: {
                    formatter: function(val, { seriesIndex, dataPointIndex, w }) {
                        const categories = w.globals.labels;
                        const categoryName = categories[dataPointIndex];

                        // Special handling for processing time
                        if (categoryName === 'Processing Speed' || categoryName === 'Efficiency Score') {
                            const actualValue = seriesIndex === 0
                                ? ({{ $coordinatorHierarchy['avg_processing_time'] ?? 0 }})
                                : ({{ $directTeam['avg_processing_time'] ?? 0 }});
                            if (categoryName === 'Processing Speed') {
                                return actualValue + ' days (normalized)';
                            } else {
                                return (100 - Math.min((actualValue / 30) * 100, 100)).toFixed(1) + '% (efficiency)';
                            }
                        }

                        return val.toFixed(2) + '%';
                    }
                }
            },
            plotOptions: {
                radar: {
                    polygons: {
                        strokeColors: '#e0e0e0',
                        fill: {
                            colors: ['#f8f9fa', '#ffffff']
                        }
                    }
                }
            }
        });
        performanceRadarChart.render();
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
