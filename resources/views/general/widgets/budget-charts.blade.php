@php
    $budgetData = $budgetOverviewData ?? [];
    $context = request('budget_context', 'combined');

    // Get data for selected context
    if ($context === 'coordinator_hierarchy') {
        $contextData = $budgetData['coordinator_hierarchy'] ?? [];
        $byProvince = $contextData['by_province'] ?? [];
        $byCenter = [];
    } elseif ($context === 'direct_team') {
        $contextData = $budgetData['direct_team'] ?? [];
        $byCenter = $contextData['by_center'] ?? [];
        $byProvince = [];
    } else {
        $contextData = $budgetData['combined'] ?? [];
        $byProvince = $budgetData['coordinator_hierarchy']['by_province'] ?? [];
        $byCenter = $budgetData['direct_team']['by_center'] ?? [];
    }

    $byProjectType = $contextData['by_project_type'] ?? [];
    $expenseTrends = $budgetData['expense_trends'] ?? [];
    $expenseMovingAvg = $budgetData['expense_moving_avg'] ?? [];
    $expenseMovingAvgCoordinator = $budgetData['expense_moving_avg_coordinator'] ?? [];
    $expenseMovingAvgDirectTeam = $budgetData['expense_moving_avg_direct_team'] ?? [];
    $expenseTrendIndicators = $budgetData['expense_trend_indicators'] ?? [];
    $budgetByContext = $budgetData['budget_by_context'] ?? [];

    // Location data for chart
    $showLocationChart = false;
    $locationData = [];
    $locationLabel = '';

    if ($context === 'coordinator_hierarchy') {
        $locationData = $byProvince;
        $locationLabel = 'Province';
        $showLocationChart = !empty($locationData);
    } elseif ($context === 'direct_team') {
        $locationData = $byCenter;
        $locationLabel = 'Center';
        $showLocationChart = !empty($locationData);
    } else {
        // Combined: Merge province and center data
        $locationData = array_merge($byProvince, $byCenter);
        $locationLabel = 'Province/Center';
        $showLocationChart = !empty($locationData);
    }
@endphp

<div class="card mb-4 widget-card" data-widget-id="budget-charts">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="pie-chart" class="me-2"></i>Budget Analytics Charts
        </h5>
        <div>
            <select class="form-select form-select-sm d-inline-block" id="budgetChartsContextFilter" style="width: auto;" onchange="setBudgetChartsContext(this.value)">
                <option value="combined" {{ $context === 'combined' || !$context ? 'selected' : '' }}>Combined</option>
                <option value="coordinator_hierarchy" {{ $context === 'coordinator_hierarchy' ? 'selected' : '' }}>Coordinator Hierarchy</option>
                <option value="direct_team" {{ $context === 'direct_team' ? 'selected' : '' }}>Direct Team</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle ms-2" data-widget="budget-charts" title="Minimize">
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="card-body widget-content">
        {{-- Chart 1: Budget by Context (Pie Chart) - Only show in Combined view --}}
        @if($context === 'combined' || !$context)
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i data-feather="pie-chart" class="me-2" style="width: 16px; height: 16px;"></i>
                                Budget by Context
                            </h6>
                        </div>
                        <div class="card-body">
                            @if(!empty($budgetByContext) && (($budgetByContext['coordinator_hierarchy'] ?? 0) > 0 || ($budgetByContext['direct_team'] ?? 0) > 0))
                                <div id="budgetByContextChart" style="min-height: 300px;"></div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i data-feather="pie-chart" style="width: 32px; height: 32px; opacity: 0.3;"></i>
                                    <p class="mt-2 mb-0">No context data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i data-feather="pie-chart" class="me-2" style="width: 16px; height: 16px;"></i>
                                Budget by Project Type
                            </h6>
                        </div>
                        <div class="card-body">
                            @if(count($byProjectType) > 0)
                                <div id="budgetByProjectTypeChart" style="min-height: 300px;"></div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i data-feather="pie-chart" style="width: 32px; height: 32px; opacity: 0.3;"></i>
                                    <p class="mt-2 mb-0">No project type data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Budget by Project Type (Full Width when not combined) --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i data-feather="pie-chart" class="me-2" style="width: 16px; height: 16px;"></i>
                                Budget by Project Type
                            </h6>
                        </div>
                        <div class="card-body">
                            @if(count($byProjectType) > 0)
                                <div id="budgetByProjectTypeChart" style="min-height: 300px;"></div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i data-feather="pie-chart" style="width: 32px; height: 32px; opacity: 0.3;"></i>
                                    <p class="mt-2 mb-0">No project type data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Chart 3: Budget vs Expenses by Project Type (Stacked Bar Chart) --}}
        @if(count($byProjectType) > 0)
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i data-feather="bar-chart-2" class="me-2" style="width: 16px; height: 16px;"></i>
                                Budget vs Expenses by Project Type ({{ $context === 'coordinator_hierarchy' ? 'Coordinator Hierarchy' : ($context === 'direct_team' ? 'Direct Team' : 'Combined') }})
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="budgetVsExpensesByProjectTypeChart" style="min-height: 350px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Chart 4: Budget by Province/Center (Stacked Bar Chart) --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i data-feather="layers" class="me-2" style="width: 16px; height: 16px;"></i>
                            Budget Breakdown by {{ $locationLabel }} ({{ $context === 'coordinator_hierarchy' ? 'Coordinator Hierarchy' : ($context === 'direct_team' ? 'Direct Team' : 'Combined') }})
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($showLocationChart)
                            <div id="budgetByLocationChart" style="min-height: 350px;"></div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i data-feather="layers" style="width: 32px; height: 32px; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">No {{ strtolower($locationLabel) }} data available</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 5: Expense Trends (Area Chart with Moving Average) --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i data-feather="trending-up" class="me-2" style="width: 16px; height: 16px;"></i>
                            Expense Trends (Last 6 Months)
                        </h6>
                        @if(!empty($expenseTrendIndicators))
                            @php
                                $expenseTrend = $expenseTrendIndicators;
                                $trendColor = $expenseTrend['direction'] === 'up' ? 'text-success' : ($expenseTrend['direction'] === 'down' ? 'text-danger' : 'text-muted');
                                $trendIcon = $expenseTrend['direction'] === 'up' ? 'trending-up' : ($expenseTrend['direction'] === 'down' ? 'trending-down' : 'minus');
                                $trendSign = $expenseTrend['change'] > 0 ? '+' : '';
                            @endphp
                            <small class="{{ $trendColor }}">
                                <i data-feather="{{ $trendIcon }}" style="width: 14px; height: 14px;"></i>
                                {{ $trendSign }}₹{{ number_format(abs($expenseTrend['change']), 2) }}
                                ({{ $trendSign }}{{ number_format($expenseTrend['change_percent'], 1) }}%)
                            </small>
                        @endif
                    </div>
                    <div class="card-body">
                        @if(count($expenseTrends) > 0)
                            <div id="expenseTrendsChart" style="min-height: 350px;"></div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i data-feather="trending-up" style="width: 32px; height: 32px; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">No expense trend data available</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function setBudgetChartsContext(context) {
    const url = new URL(window.location.href);
    url.searchParams.set('budget_context', context);
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Chart 1: Budget by Context (Pie Chart) - Only for Combined view
    @if(($context === 'combined' || !$context) && !empty($budgetByContext))
        @php
            $contextChartData = [
                'Coordinator Hierarchy' => $budgetByContext['coordinator_hierarchy'] ?? 0,
                'Direct Team' => $budgetByContext['direct_team'] ?? 0
            ];
        @endphp
        var budgetByContextElement = document.querySelector("#budgetByContextChart");
        if (budgetByContextElement && typeof ApexCharts !== 'undefined') {
            var budgetByContextOptions = {
                series: @json(array_values($contextChartData)),
                chart: {
                    type: 'pie',
                    height: 300,
                },
                labels: @json(array_keys($contextChartData)),
                colors: ['#3b82f6', '#10b981'],
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
            var budgetByContextChart = new ApexCharts(budgetByContextElement, budgetByContextOptions);
            budgetByContextChart.render();
        }
    @endif

    // Chart 2: Budget by Project Type (Pie Chart)
    @if(count($byProjectType) > 0)
        var budgetByProjectTypeElement = document.querySelector("#budgetByProjectTypeChart");
        if (budgetByProjectTypeElement && typeof ApexCharts !== 'undefined') {
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

    // Chart 3: Budget vs Expenses by Project Type (Stacked Bar Chart)
    @if(count($byProjectType) > 0)
        var budgetVsExpensesByProjectTypeElement = document.querySelector("#budgetVsExpensesByProjectTypeChart");
        if (budgetVsExpensesByProjectTypeElement && typeof ApexCharts !== 'undefined') {
            @php
                $projectTypes = array_keys($byProjectType);
                $budgetData = array_map(function($item) { return $item['budget'] ?? 0; }, $byProjectType);
                $expensesData = array_map(function($item) { return $item['approved_expenses'] ?? 0; }, $byProjectType);
                $remainingData = array_map(function($item) { return ($item['budget'] ?? 0) - ($item['approved_expenses'] ?? 0); }, $byProjectType);
            @endphp
            var budgetVsExpensesOptions = {
                series: [
                    {
                        name: 'Approved Expenses',
                        data: @json($expensesData)
                    },
                    {
                        name: 'Remaining Budget',
                        data: @json($remainingData)
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
                        borderRadius: 4
                    }
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: @json($projectTypes),
                    labels: {
                        rotate: -45,
                        rotateAlways: true
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                        }
                    }
                },
                colors: ['#10b981', '#e5e7eb'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (val) {
                            return "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 4
                }
            };
            var budgetVsExpensesChart = new ApexCharts(budgetVsExpensesByProjectTypeElement, budgetVsExpensesOptions);
            budgetVsExpensesChart.render();
        }
    @endif

    // Chart 4: Budget by Province/Center (Stacked Horizontal Bar Chart)
    @if($showLocationChart && !empty($locationData))
        var budgetByLocationElement = document.querySelector("#budgetByLocationChart");
        if (budgetByLocationElement && typeof ApexCharts !== 'undefined') {
            @php
                $locationNames = array_keys($locationData);
                $locationBudgetData = array_map(function($item) { return $item['budget'] ?? 0; }, $locationData);
                $locationExpensesData = array_map(function($item) { return $item['approved_expenses'] ?? 0; }, $locationData);
                $locationRemainingData = array_map(function($item) { return ($item['budget'] ?? 0) - ($item['approved_expenses'] ?? 0); }, $locationData);
            @endphp
            var budgetByLocationOptions = {
                series: [
                    {
                        name: 'Approved Expenses',
                        data: @json($locationExpensesData)
                    },
                    {
                        name: 'Remaining Budget',
                        data: @json($locationRemainingData)
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 350,
                    stacked: true,
                    horizontal: true,
                    toolbar: { show: true },
                    zoom: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        columnWidth: '60%'
                    }
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: @json($locationNames),
                    labels: {
                        formatter: function (val) {
                            return "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                        }
                    }
                },
                yaxis: {
                    labels: {
                        maxWidth: 150
                    }
                },
                colors: ['#10b981', '#e5e7eb'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (val) {
                            return "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 4
                }
            };
            var budgetByLocationChart = new ApexCharts(budgetByLocationElement, budgetByLocationOptions);
            budgetByLocationChart.render();
        }
    @endif

    // Chart 5: Expense Trends (Area Chart with Moving Average)
    @if(count($expenseTrends) > 0)
        var expenseTrendsElement = document.querySelector("#expenseTrendsChart");
        if (expenseTrendsElement && typeof ApexCharts !== 'undefined') {
            @php
                $expenseMovingAvgData = $expenseMovingAvg ?? [];
                $expenseMovingAvgCoordinatorData = $expenseMovingAvgCoordinator ?? [];
                $expenseMovingAvgDirectTeamData = $expenseMovingAvgDirectTeam ?? [];
                $isCombinedContext = ($context === 'combined' || !$context);
            @endphp

            @if($isCombinedContext)
                var expenseSeries = [
                    {
                        name: 'Coordinator Hierarchy',
                        data: @json(array_column($expenseTrends, 'coordinator_hierarchy_expenses')),
                        type: 'area'
                    },
                    {
                        name: 'Direct Team',
                        data: @json(array_column($expenseTrends, 'direct_team_expenses')),
                        type: 'area'
                    },
                    {
                        name: 'Total',
                        data: @json(array_column($expenseTrends, 'expenses')),
                        type: 'area'
                    }
                ];

                // Add moving averages if available
                @if(count($expenseMovingAvgCoordinatorData) > 0 && count($expenseMovingAvgDirectTeamData) > 0 && count($expenseMovingAvgData) > 0)
                    expenseSeries.push({
                        name: 'Moving Avg - Coordinator',
                        data: @json($expenseMovingAvgCoordinatorData),
                        type: 'line'
                    });
                    expenseSeries.push({
                        name: 'Moving Avg - Direct Team',
                        data: @json($expenseMovingAvgDirectTeamData),
                        type: 'line'
                    });
                    expenseSeries.push({
                        name: 'Moving Avg - Total',
                        data: @json($expenseMovingAvgData),
                        type: 'line'
                    });
                @endif
            @else
                var expenseSeries = [
                    {
                        name: 'Expenses',
                        data: @json(array_column($expenseTrends, 'expenses')),
                        type: 'area'
                    }
                ];

                // Add moving average if available
                @if(count($expenseMovingAvgData) > 0)
                    expenseSeries.push({
                        name: 'Moving Average (3-month)',
                        data: @json($expenseMovingAvgData),
                        type: 'line'
                    });
                @endif
            @endif

            var expenseTrendsOptions = {
                series: expenseSeries,
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: { show: true },
                    zoom: { enabled: true },
                    stacked: false
                },
                stroke: {
                    curve: 'smooth',
                    width: $isCombinedContext ? [3, 2, 3, 2, 2, 2] : [3, 2],
                    dashArray: $isCombinedContext ? [0, 0, 0, 5, 5, 5] : [0, 5]
                },
                markers: {
                    size: $isCombinedContext ? [5, 4, 5, 0, 0, 0] : [5, 0],
                    hover: { size: 7 }
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: @json(array_column($expenseTrends, 'month')),
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return val !== null ? "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0}) : '';
                        }
                    },
                    min: 0
                },
                colors: $isCombinedContext
                    ? ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#6366f1']
                    : ['#10b981', '#8b5cf6'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.9,
                        stops: [0, 90, 100]
                    }
                },
                legend: {
                    position: 'bottom',
                    show: true
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (val) {
                            return val !== null ? "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 2}) : 'N/A';
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 4
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
