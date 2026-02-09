@php
    // Get budget data for charts (same data as Budget Overview widget)
    $budgetData = $systemBudgetOverviewData ?? [];
    $byProjectType = $budgetData['by_project_type'] ?? [];
    $byProvince = $budgetData['by_province'] ?? [];
    $expenseTrends = $budgetData['expense_trends'] ?? [];
@endphp

<div class="row mb-4">
    {{-- Budget Charts Section --}}
    <div class="col-md-12">
        <h5 class="text-muted mb-3">Budget Analytics</h5>
    </div>
</div>

{{-- Breakdown Charts Row 1 --}}
<div class="row mb-4">
    <div class="col-md-6 col-lg-6 mb-4">
        <div class="card widget-card" data-widget-id="budget-by-project-type-chart">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Budget by Project Type</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="budget-by-project-type-chart" title="Minimize">−</button>
            </div>
            <div class="card-body widget-content">
                @if(count($byProjectType) > 0)
                    <div id="budgetByProjectTypeChart" style="min-height: 300px;"></div>
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-0">No project type data available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-6 mb-4">
        <div class="card widget-card" data-widget-id="budget-by-province-chart">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Budget by Province</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="budget-by-province-chart" title="Minimize">−</button>
            </div>
            <div class="card-body widget-content">
                @if(count($byProvince) > 0)
                    <div id="budgetByProvinceChart" style="min-height: 300px;"></div>
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-0">No province data available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Expense Trends Chart --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card widget-card" data-widget-id="expense-trends-chart">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Expense Trends (Last 6 Months)</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="expense-trends-chart" title="Minimize">−</button>
            </div>
            <div class="card-body widget-content">
                @if(count($expenseTrends) > 0)
                    <div id="expenseTrendsChart" style="min-height: 350px;"></div>
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-0">No expense trend data available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Budget by Project Type Chart (Pie Chart)
    @if(count($byProjectType) > 0)
    var budgetByProjectTypeElement = document.querySelector("#budgetByProjectTypeChart");
    if (budgetByProjectTypeElement) {
        var budgetByProjectTypeOptions = {
            series: @json(array_column($byProjectType, 'budget')),
            chart: {
                type: 'pie',
                height: 300,
            },
            labels: @json(array_keys($byProjectType)),
            colors: ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#14b8a6', '#ec4899'],
            legend: {
                position: 'bottom',
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 2});
                    }
                }
            }
        };
        var budgetByProjectTypeChart = new ApexCharts(budgetByProjectTypeElement, budgetByProjectTypeOptions);
        budgetByProjectTypeChart.render();
    }
    @endif

    // Budget by Province Chart (Horizontal Bar Chart)
    @if(count($byProvince) > 0)
    var budgetByProvinceElement = document.querySelector("#budgetByProvinceChart");
    if (budgetByProvinceElement) {
        var budgetByProvinceOptions = {
            series: [{
                name: 'Budget',
                data: @json(array_column($byProvince, 'budget'))
            }],
            chart: {
                type: 'bar',
                height: 300,
                horizontal: true,
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: @json(array_keys($byProvince)),
            },
            colors: ['#3b82f6'],
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 2});
                    }
                }
            }
        };
        var budgetByProvinceChart = new ApexCharts(budgetByProvinceElement, budgetByProvinceOptions);
        budgetByProvinceChart.render();
    }
    @endif

    // Expense Trends Chart (Area Chart)
    @if(count($expenseTrends) > 0)
    var expenseTrendsElement = document.querySelector("#expenseTrendsChart");
    if (expenseTrendsElement) {
        var expenseTrendsOptions = {
            series: [{
                name: 'Expenses',
                data: @json(array_column($expenseTrends, 'expenses'))
            }],
            chart: {
                type: 'area',
                height: 350,
                toolbar: {
                    show: true
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
                categories: @json(array_column($expenseTrends, 'month')),
            },
            colors: ['#10b981'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.9,
                    stops: [0, 90, 100]
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 2});
                    }
                }
            }
        };
        var expenseTrendsChart = new ApexCharts(expenseTrendsElement, expenseTrendsOptions);
        expenseTrendsChart.render();
    }
    @endif

    // Re-initialize feather icons after content loads
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endpush
