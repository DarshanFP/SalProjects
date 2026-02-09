{{-- Project Status Visualization Widget - Dark Theme Compatible with ApexCharts --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Project Status Overview</h5>
        <div class="widget-drag-handle"></div>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Project Status Distribution (Donut Chart) --}}
            <div class="col-md-6 mb-4 mb-md-0">
                <h6 class="mb-3 text-center">Project Status Distribution</h6>
                <div id="projectStatusChart" style="min-height: 280px;"></div>
            </div>

            {{-- Project Type Distribution (Pie Chart) --}}
            <div class="col-md-6">
                <h6 class="mb-3 text-center">Project Type Distribution</h6>
                <div id="projectTypeChart" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize charts if ApexCharts is available
    if (typeof ApexCharts !== 'undefined') {
        initializeProjectStatusCharts();
    }
});

// Dark theme colors for ApexCharts
const projectChartColors = {
    approved: '#05a34a',      // Success (Green)
    draft: '#6b7280',          // Secondary (Gray)
    pending: '#fbbc06',        // Warning (Yellow)
    reverted: '#ff3366',       // Danger (Red)
    rejected: '#ef4444',       // Danger (Red)
    colors: ['#6571ff', '#05a34a', '#fbbc06', '#ff3366', '#66d1d1', '#ec4899', '#10b981', '#3b82f6']
};

let projectStatusChart = null;
let projectTypeChart = null;

function initializeProjectStatusCharts() {
    // Get projects from paginator or collection
    const projectsData = @json(isset($projects) && method_exists($projects, 'items') ? $projects->items() : (is_array($projects) ? $projects : []));
    const enhancedProjects = @json($enhancedProjects ?? []);

    // Project Status Distribution
    if (document.querySelector("#projectStatusChart") && projectsData && projectsData.length > 0) {
        const statusCounts = {};
        
        projectsData.forEach(project => {
            const status = project.status || 'unknown';
            statusCounts[status] = (statusCounts[status] || 0) + 1;
        });

        const statusLabels = Object.keys(statusCounts);
        const statusValues = Object.values(statusCounts);
        
        // Map status to colors
        const statusColors = statusLabels.map(status => {
            if (status.includes('approved')) return projectChartColors.approved;
            if (status.includes('draft')) return projectChartColors.draft;
            if (status.includes('pending') || status.includes('submitted') || status.includes('forwarded')) return projectChartColors.pending;
            if (status.includes('reverted')) return projectChartColors.reverted;
            if (status.includes('rejected')) return projectChartColors.rejected;
            return projectChartColors.colors[0];
        });

        const statusChartOptions = {
            series: statusValues,
            chart: {
                type: 'donut',
                height: 280,
                foreColor: '#d0d6e1',
                background: 'transparent'
            },
            labels: statusLabels.map(status => {
                return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }),
            colors: statusColors,
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
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Projects',
                                formatter: function() {
                                    return statusValues.reduce((a, b) => a + b, 0).toString();
                                },
                                color: '#d0d6e1'
                            },
                            value: {
                                formatter: function(val) {
                                    return val.toString();
                                },
                                color: '#d0d6e1'
                            }
                        }
                    }
                }
            }
        };

        projectStatusChart = new ApexCharts(document.querySelector("#projectStatusChart"), statusChartOptions);
        projectStatusChart.render();
    }

    // Project Type Distribution
    if (document.querySelector("#projectTypeChart") && projectsData && projectsData.length > 0) {
        const typeCounts = {};
        
        projectsData.forEach(project => {
            const type = project.project_type || 'Unknown';
            typeCounts[type] = (typeCounts[type] || 0) + 1;
        });

        const typeLabels = Object.keys(typeCounts);
        const typeValues = Object.values(typeCounts);

        const typeChartOptions = {
            series: typeValues,
            chart: {
                type: 'pie',
                height: 280,
                foreColor: '#d0d6e1',
                background: 'transparent'
            },
            labels: typeLabels,
            colors: projectChartColors.colors.slice(0, typeLabels.length),
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
                    expandOnClick: true,
                    donut: {
                        labels: {
                            show: false
                        }
                    }
                }
            }
        };

        projectTypeChart = new ApexCharts(document.querySelector("#projectTypeChart"), typeChartOptions);
        projectTypeChart.render();
    } else {
        // Show empty state message
        document.querySelector("#projectStatusChart").innerHTML = '<div class="text-center py-5 text-muted"><p>No projects to display</p></div>';
        document.querySelector("#projectTypeChart").innerHTML = '<div class="text-center py-5 text-muted"><p>No projects to display</p></div>';
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }
}
</script>
@endpush
