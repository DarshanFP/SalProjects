{{-- Budget Analytics Widget - Dark Theme Compatible with ApexCharts --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="pie-chart" class="me-2"></i>
            Budget Analytics
        </h5>
        <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary active" onclick="showBudgetChart(event, 'utilization')">
                <i data-feather="trending-up" style="width: 14px; height: 14px;"></i> Utilization
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="showBudgetChart(event, 'distribution')">
                <i data-feather="pie-chart" style="width: 14px; height: 14px;"></i> Distribution
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="showBudgetChart(event, 'comparison')">
                <i data-feather="bar-chart-2" style="width: 14px; height: 14px;"></i> Comparison
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="showBudgetChart(event, 'trends')">
                <i data-feather="activity" style="width: 14px; height: 14px;"></i> Trends
            </button>
            </div>
            <div class="widget-drag-handle ms-2">
                <i data-feather="move" style="width: 16px; height: 16px;" class="text-muted"></i>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Budget Utilization Timeline Chart --}}
        <div id="budgetUtilizationChartContainer" class="chart-container">
            <div id="budgetUtilizationChart" style="min-height: 300px;"></div>
        </div>

        {{-- Budget Distribution Chart --}}
        <div id="budgetDistributionChartContainer" class="chart-container" style="display: none;">
            <div id="budgetDistributionChart" style="min-height: 300px;"></div>
        </div>

        {{-- Budget vs Expenses Comparison Chart --}}
        <div id="budgetComparisonChartContainer" class="chart-container" style="display: none;">
            <div id="budgetComparisonChart" style="min-height: 300px;"></div>
        </div>

        {{-- Expense Trends Chart --}}
        <div id="budgetTrendsChartContainer" class="chart-container" style="display: none;">
            <div id="budgetTrendsChart" style="min-height: 300px;"></div>
        </div>

        {{-- Summary Stats --}}
        <div class="mt-3 pt-3 border-top border-secondary">
            <div class="row g-3 text-center">
                <div class="col-4">
                    <small class="text-muted d-block">Total Budget</small>
                    <strong class="text-white">{{ format_indian_currency($chartData['total_budget'] ?? 0, 2) }}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Total Expenses</small>
                    <strong class="text-white">{{ format_indian_currency($chartData['total_expenses'] ?? 0, 2) }}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Remaining</small>
                    <strong class="text-white">{{ format_indian_currency($chartData['total_remaining'] ?? 0, 2) }}</strong>
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
        initializeBudgetCharts();
    }
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
let budgetUtilizationChart = null;
let budgetDistributionChart = null;
let budgetComparisonChart = null;
let budgetTrendsChart = null;

function initializeBudgetCharts() {
    const chartData = @json($chartData);

    // Budget Utilization Timeline Chart (Line Chart)
    if (document.querySelector("#budgetUtilizationChart")) {
        const utilizationData = chartData.budget_utilization_timeline || {};
        const months = Object.keys(utilizationData);
        const expenses = months.map(month => utilizationData[month].expenses || 0);
        const budget = months.map(month => utilizationData[month].budget || 0);
        const remaining = months.map(month => utilizationData[month].remaining || 0);
        const utilization = months.map(month => utilizationData[month].utilization || 0);

        const utilizationOptions = {
            series: [
                {
                    name: 'Total Expenses',
                    type: 'area',
                    data: expenses
                },
                {
                    name: 'Total Budget',
                    type: 'line',
                    data: budget
                },
                {
                    name: 'Remaining Budget',
                    type: 'area',
                    data: remaining
                },
                {
                    name: 'Utilization %',
                    type: 'line',
                    data: utilization
                }
            ],
            chart: {
                height: 300,
                type: 'line',
                stacked: false,
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
                foreColor: '#d0d6e1', // Text color for dark theme
                background: 'transparent'
            },
            colors: [darkThemeColors.danger, darkThemeColors.info, darkThemeColors.success, darkThemeColors.warning],
            stroke: {
                width: [2, 2, 2, 3],
                curve: ['smooth', 'smooth', 'smooth', 'straight']
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'vertical',
                    shadeIntensity: 0.3,
                    gradientToColors: [darkThemeColors.danger, darkThemeColors.info, darkThemeColors.success, darkThemeColors.warning],
                    inverseColors: false,
                    opacityFrom: 0.5,
                    opacityTo: 0.1,
                    stops: [0, 50, 100]
                },
                opacity: [0.5, 0.2, 0.3, 1]
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
            yaxis: [
                {
                    title: {
                        text: 'Amount (₱)',
                        style: {
                            color: '#d0d6e1'
                        }
                    },
                    labels: {
                        formatter: function(val) {
                            return '₱' + (val / 1000).toFixed(1) + 'K';
                        },
                        style: {
                            colors: '#7987a1'
                        }
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Utilization %',
                        style: {
                            color: '#d0d6e1'
                        }
                    },
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(1) + '%';
                        },
                        style: {
                            colors: '#7987a1'
                        }
                    },
                    max: 100
                }
            ],
            legend: {
                position: 'bottom',
                labels: {
                    colors: '#d0d6e1'
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 3) {
                            return val.toFixed(2) + '%';
                        }
                        return '₱' + val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            },
            grid: {
                borderColor: '#212a3a',
                strokeDashArray: 4
            }
        };

        budgetUtilizationChart = new ApexCharts(document.querySelector("#budgetUtilizationChart"), utilizationOptions);
        budgetUtilizationChart.render();
    }

    // Budget Distribution by Project Type (Donut Chart)
    if (document.querySelector("#budgetDistributionChart")) {
        const budgetByType = chartData.budget_by_type || {};
        const labels = Object.keys(budgetByType);
        const values = Object.values(budgetByType);

        const distributionOptions = {
            series: values,
            chart: {
                type: 'donut',
                height: 300,
                foreColor: '#d0d6e1',
                background: 'transparent'
            },
            labels: labels,
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
                        return '₱' + val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                                label: 'Total Budget',
                                formatter: function() {
                                    return '₱' + (values.reduce((a, b) => a + b, 0)).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                },
                                color: '#d0d6e1'
                            },
                            value: {
                                formatter: function(val) {
                                    return '₱' + (val / 1000).toFixed(1) + 'K';
                                },
                                color: '#d0d6e1'
                            }
                        }
                    }
                }
            }
        };

        budgetDistributionChart = new ApexCharts(document.querySelector("#budgetDistributionChart"), distributionOptions);
        budgetDistributionChart.render();
    }

    // Budget vs Expenses Comparison (Stacked Bar Chart)
    if (document.querySelector("#budgetComparisonChart")) {
        const comparisonData = chartData.budget_vs_expenses || {};
        const labels = Object.keys(comparisonData);
        const budgetData = labels.map(type => comparisonData[type].budget || 0);
        const expensesData = labels.map(type => comparisonData[type].expenses || 0);
        const remainingData = labels.map(type => comparisonData[type].remaining || 0);

        const comparisonOptions = {
            series: [
                {
                    name: 'Budget',
                    data: budgetData
                },
                {
                    name: 'Expenses',
                    data: expensesData
                },
                {
                    name: 'Remaining',
                    data: remainingData
                }
            ],
            chart: {
                type: 'bar',
                height: 300,
                stacked: false,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: true
                    }
                },
                foreColor: '#d0d6e1',
                background: 'transparent'
            },
            colors: [darkThemeColors.info, darkThemeColors.danger, darkThemeColors.success],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    borderRadius: 4
                }
            },
            xaxis: {
                categories: labels,
                labels: {
                    style: {
                        colors: '#7987a1'
                    },
                    rotate: -45,
                    rotateAlways: false
                }
            },
            yaxis: {
                title: {
                    text: 'Amount (₱)',
                    style: {
                        color: '#d0d6e1'
                    }
                },
                labels: {
                    formatter: function(val) {
                        return '₱' + (val / 1000).toFixed(1) + 'K';
                    },
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
                        return '₱' + val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            },
            grid: {
                borderColor: '#212a3a',
                strokeDashArray: 4
            }
        };

        budgetComparisonChart = new ApexCharts(document.querySelector("#budgetComparisonChart"), comparisonOptions);
        budgetComparisonChart.render();
    }

    // Expense Trends Chart (Area Chart showing monthly expenses over time)
    if (document.querySelector("#budgetTrendsChart") && chartData.monthly_expenses) {
        const monthlyExpenses = chartData.monthly_expenses || {};
        const months = Object.keys(monthlyExpenses).sort();
        const expenses = months.map(month => monthlyExpenses[month] || 0);

        if (expenses.length > 0) {
            const trendsChartOptions = {
                series: [{
                    name: 'Monthly Expenses',
                    data: expenses
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    stacked: false,
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
                colors: [darkThemeColors.danger],
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        type: 'vertical',
                        shadeIntensity: 0.4,
                        gradientToColors: [darkThemeColors.warning],
                        inverseColors: false,
                        opacityFrom: 0.6,
                        opacityTo: 0.1,
                        stops: [0, 50, 100]
                    },
                    opacity: 0.6
                },
                markers: {
                    size: 4,
                    hover: {
                        size: 6
                    }
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
                        text: 'Expenses (₱)',
                        style: {
                            color: '#d0d6e1'
                        }
                    },
                    labels: {
                        formatter: function(val) {
                            return '₱' + (val / 1000).toFixed(1) + 'K';
                        },
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
                            return '₱' + val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                },
                grid: {
                    borderColor: '#212a3a',
                    strokeDashArray: 4
                },
                dataLabels: {
                    enabled: false
                }
            };

            budgetTrendsChart = new ApexCharts(document.querySelector("#budgetTrendsChart"), trendsChartOptions);
            budgetTrendsChart.render();
        } else {
            document.querySelector("#budgetTrendsChart").innerHTML = '<div class="text-center py-5 text-muted"><i data-feather="inbox" style="width: 32px; height: 32px;" class="mb-2"></i><p>No expense trends data available</p></div>';
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    }
}

// Show/hide chart containers
function showBudgetChart(event, chartType) {
    // Hide all chart containers
    document.querySelectorAll('#budgetUtilizationChartContainer, #budgetDistributionChartContainer, #budgetComparisonChartContainer, #budgetTrendsChartContainer').forEach(container => {
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
    if (chartType === 'utilization') {
        container = document.getElementById('budgetUtilizationChartContainer');
    } else if (chartType === 'distribution') {
        container = document.getElementById('budgetDistributionChartContainer');
    } else if (chartType === 'comparison') {
        container = document.getElementById('budgetComparisonChartContainer');
    } else if (chartType === 'trends') {
        container = document.getElementById('budgetTrendsChartContainer');
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
        if (chartType === 'utilization' && budgetUtilizationChart) {
            budgetUtilizationChart.resize();
        } else if (chartType === 'distribution' && budgetDistributionChart) {
            budgetDistributionChart.resize();
        } else if (chartType === 'comparison' && budgetComparisonChart) {
            budgetComparisonChart.resize();
        } else if (chartType === 'trends' && budgetTrendsChart) {
            budgetTrendsChart.resize();
        }
    }, 100);
}

// Make function available globally
window.showBudgetChart = showBudgetChart;
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
