{{-- Report Analytics Widget - Dark Theme Compatible with ApexCharts --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Report Analytics</h5>
        <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" onclick="showReportChart(event, 'status')">Status</button>
                <button type="button" class="btn btn-outline-secondary" onclick="showReportChart(event, 'timeline')">Timeline</button>
                <button type="button" class="btn btn-outline-secondary" onclick="showReportChart(event, 'completion')">Completion</button>
            </div>
            <div class="widget-drag-handle ms-2"></div>
        </div>
    </div>
    <div class="card-body">
        {{-- Report Status Distribution Chart --}}
        <div id="reportStatusChartContainer" class="chart-container">
            <div id="reportStatusChart" style="min-height: 300px;"></div>
        </div>

        {{-- Report Submission Timeline Chart --}}
        <div id="reportTimelineChartContainer" class="chart-container" style="display: none;">
            <div id="reportTimelineChart" style="min-height: 300px;"></div>
        </div>

        {{-- Report Completion Rate Gauge Chart --}}
        <div id="reportCompletionChartContainer" class="chart-container" style="display: none;">
            <div id="reportCompletionChart" style="min-height: 300px;"></div>
        </div>

        {{-- Summary Stats --}}
        <div class="mt-3 pt-3 border-top border-secondary">
            <div class="row g-3 text-center">
                <div class="col-4">
                    <small class="text-muted d-block">Total Reports</small>
                    <strong class="text-white">{{ $reportChartData['total_reports'] ?? 0 }}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Approved</small>
                    <strong class="text-success">{{ $reportChartData['approved_reports'] ?? 0 }}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Completion Rate</small>
                    <strong class="text-white">{{ format_indian_percentage($reportChartData['completion_rate'] ?? 0, 1) }}</strong>
                </div>
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
        initializeReportCharts();
    }
});

// Dark theme colors for ApexCharts
const reportChartColors = {
    draft: '#6b7280',           // Secondary (Gray)
    submitted: '#66d1d1',       // Info (Cyan)
    forwarded: '#6571ff',       // Primary (Blue)
    approved: '#05a34a',        // Success (Green)
    reverted: '#ff3366',        // Danger (Red)
    rejected: '#ef4444',        // Danger (Red)
    colors: ['#6571ff', '#05a34a', '#fbbc06', '#ff3366', '#66d1d1', '#ec4899', '#10b981', '#3b82f6']
};

// Chart instances
let reportStatusChart = null;
let reportTimelineChart = null;
let reportCompletionChart = null;

function initializeReportCharts() {
    const reportChartData = @json($reportChartData ?? []);

    // Report Status Distribution Chart (Donut Chart)
    if (document.querySelector("#reportStatusChart") && reportChartData.status_distribution) {
        const statusData = reportChartData.status_distribution || {};
        const labels = Object.keys(statusData);
        const values = Object.values(statusData);

        // Filter out zero values for cleaner chart
        const filteredLabels = [];
        const filteredValues = [];
        const filteredColors = [];

        labels.forEach((status, index) => {
            if (values[index] > 0) {
                filteredLabels.push(status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
                filteredValues.push(values[index]);

                // Map status to color
                if (status.includes('approved')) {
                    filteredColors.push(reportChartColors.approved);
                } else if (status.includes('submitted')) {
                    filteredColors.push(reportChartColors.submitted);
                } else if (status.includes('forwarded')) {
                    filteredColors.push(reportChartColors.forwarded);
                } else if (status.includes('reverted')) {
                    filteredColors.push(reportChartColors.reverted);
                } else if (status.includes('rejected')) {
                    filteredColors.push(reportChartColors.rejected);
                } else {
                    filteredColors.push(reportChartColors.draft);
                }
            }
        });

        if (filteredValues.length > 0) {
            const statusChartOptions = {
                series: filteredValues,
                chart: {
                    type: 'donut',
                    height: 300,
                    foreColor: '#d0d6e1',
                    background: 'transparent'
                },
                labels: filteredLabels,
                colors: filteredColors,
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
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Reports',
                                    formatter: function() {
                                        return filteredValues.reduce((a, b) => a + b, 0).toString();
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

            reportStatusChart = new ApexCharts(document.querySelector("#reportStatusChart"), statusChartOptions);
            reportStatusChart.render();
        } else {
            document.querySelector("#reportStatusChart").innerHTML = '<div class="text-center py-5 text-muted"><p>No reports to display</p></div>';
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    }

    // Report Submission Timeline Chart (Line/Area Chart)
    if (document.querySelector("#reportTimelineChart") && reportChartData.monthly_submission_timeline) {
        const timelineData = reportChartData.monthly_submission_timeline || {};
        const months = Object.keys(timelineData).sort();
        const counts = months.map(month => timelineData[month] || 0);

        if (counts.length > 0) {
            const timelineChartOptions = {
                series: [{
                    name: 'Reports Submitted',
                    data: counts
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: false,
                            reset: true
                        }
                    },
                    foreColor: '#d0d6e1',
                    background: 'transparent'
                },
                colors: [reportChartColors.primary],
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        type: 'vertical',
                        shadeIntensity: 0.3,
                        gradientToColors: [reportChartColors.info],
                        inverseColors: false,
                        opacityFrom: 0.5,
                        opacityTo: 0.1,
                        stops: [0, 50, 100]
                    },
                    opacity: 0.5
                },
                xaxis: {
                    categories: months.map(month => {
                        const date = new Date(month + '-01');
                        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                    }),
                    labels: {
                        style: {
                            colors: '#7987a1'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Number of Reports',
                        style: {
                            color: '#d0d6e1'
                        }
                    },
                    labels: {
                        style: {
                            colors: '#7987a1'
                        }
                    }
                },
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
                grid: {
                    borderColor: '#212a3a',
                    strokeDashArray: 4
                }
            };

            reportTimelineChart = new ApexCharts(document.querySelector("#reportTimelineChart"), timelineChartOptions);
            reportTimelineChart.render();
        } else {
            document.querySelector("#reportTimelineChart").innerHTML = '<div class="text-center py-5 text-muted"><p>No timeline data available</p></div>';
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    }

    // Report Completion Rate Gauge Chart (Radial Chart)
    if (document.querySelector("#reportCompletionChart") && reportChartData.completion_rate !== undefined) {
        const completionRate = reportChartData.completion_rate || 0;

        const completionChartOptions = {
            series: [completionRate],
            chart: {
                type: 'radialBar',
                height: 300,
                foreColor: '#d0d6e1',
                background: 'transparent'
            },
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '60%'
                    },
                    dataLabels: {
                        name: {
                            show: true,
                            fontSize: '16px',
                            fontFamily: 'Roboto',
                            fontWeight: 500,
                            color: '#d0d6e1',
                            offsetY: -10
                        },
                        value: {
                            show: true,
                            fontSize: '24px',
                            fontFamily: 'Roboto',
                            fontWeight: 700,
                            color: '#d0d6e1',
                            formatter: function(val) {
                                return val.toFixed(1) + '%';
                            },
                            offsetY: 10
                        }
                    },
                    track: {
                        background: '#212a3a',
                        strokeWidth: '100%'
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: [completionRate >= 80 ? reportChartColors.approved : (completionRate >= 50 ? reportChartColors.warning : reportChartColors.danger)],
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 0.8,
                    stops: [0, 100]
                },
                colors: [completionRate >= 80 ? reportChartColors.approved : (completionRate >= 50 ? reportChartColors.warning : reportChartColors.danger)]
            },
            labels: ['Completion Rate'],
            stroke: {
                lineCap: 'round'
            }
        };

        reportCompletionChart = new ApexCharts(document.querySelector("#reportCompletionChart"), completionChartOptions);
        reportCompletionChart.render();
    }
}

// Show/hide chart containers
function showReportChart(event, chartType) {
    // Hide all chart containers
    document.querySelectorAll('#reportStatusChartContainer, #reportTimelineChartContainer, #reportCompletionChartContainer').forEach(container => {
        if (container) container.style.display = 'none';
    });

    // Remove active class from all buttons in this widget
    const btnGroup = event.target.closest('.btn-group');
    if (btnGroup) {
        btnGroup.querySelectorAll('.btn').forEach(btn => {
            btn.classList.remove('active');
        });
    }

    // Show selected chart
    let container = null;
    if (chartType === 'status') {
        container = document.getElementById('reportStatusChartContainer');
    } else if (chartType === 'timeline') {
        container = document.getElementById('reportTimelineChartContainer');
    } else if (chartType === 'completion') {
        container = document.getElementById('reportCompletionChartContainer');
    }

    if (container) {
        container.style.display = 'block';
    }

    // Add active class to clicked button
    if (event && event.target) {
        event.target.classList.add('active');
    }

    // Resize chart when shown
    setTimeout(() => {
        if (chartType === 'status' && reportStatusChart) {
            reportStatusChart.resize();
        } else if (chartType === 'timeline' && reportTimelineChart) {
            reportTimelineChart.resize();
        } else if (chartType === 'completion' && reportCompletionChart) {
            reportCompletionChart.resize();
        }
    }, 100);
}

// Make function available globally
window.showReportChart = showReportChart;
</script>
@endpush

<style>
.chart-container {
    min-height: 300px;
}

.btn-group-sm .btn.active {
    background-color: #6571ff;
    border-color: #6571ff;
    color: #fff;
}

.btn-group-sm .btn:hover:not(.active) {
    background-color: #4c57cc;
    border-color: #4c57cc;
    color: #fff;
}
</style>
