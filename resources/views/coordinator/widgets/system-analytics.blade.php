{{-- System Analytics Charts Widget --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Analytics</h5>
        <div class="btn-group">
            <select class="form-select form-select-sm" id="timeRangeSelector" onchange="updateAnalytics()">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 3 Months</option>
                <option value="180">Last 6 Months</option>
                <option value="365">Last Year</option>
                <option value="custom">Custom Range</option>
            </select>
            <button type="button" class="btn btn-sm btn-secondary" onclick="exportAnalytics()">Export</button>
        </div>
    </div>
    <div class="card-body">
        @if(isset($systemAnalyticsData))
            {{-- Custom Date Range Selector (Hidden by default) --}}
            <div id="customDateRange" class="row mb-3" style="display: none;">
                <div class="col-md-5">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control form-control-sm" id="startDate">
                </div>
                <div class="col-md-5">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" class="form-control form-control-sm" id="endDate">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-primary w-100" onclick="applyCustomRange()">Apply</button>
                </div>
            </div>

            {{-- Budget Analytics Charts Row --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Budget Utilization Timeline</h6>
                        </div>
                        <div class="card-body">
                            <div id="budgetUtilizationChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Budget Distribution by Province</h6>
                        </div>
                        <div class="card-body">
                            <div id="budgetByProvinceChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Budget Distribution by Project Type --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Budget Distribution by Project Type</h6>
                        </div>
                        <div class="card-body">
                            <div id="budgetByProjectTypeChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Expense Trends Over Time</h6>
                        </div>
                        <div class="card-body">
                            <div id="expenseTrendsChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Approval Rate Trends --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Approval Rate Trends</h6>
                        </div>
                        <div class="card-body">
                            <div id="approvalRateChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Report Submission Timeline</h6>
                        </div>
                        <div class="card-body">
                            <div id="reportSubmissionChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Province Performance Comparison --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Province Performance Comparison</h6>
                        </div>
                        <div class="card-body">
                            <div id="provinceComparisonChart" style="min-height: 400px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-4">
                <p class="text-muted">No analytics data available</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
let analyticsCharts = {};

function updateAnalytics() {
    const timeRange = document.getElementById('timeRangeSelector').value;
    const customRange = document.getElementById('customDateRange');
    
    if (timeRange === 'custom') {
        customRange.style.display = 'block';
    } else {
        customRange.style.display = 'none';
        loadAnalytics(timeRange);
    }
}

function applyCustomRange() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date must be before end date');
        return;
    }
    
    loadAnalytics('custom', startDate, endDate);
}

function loadAnalytics(timeRange, startDate = null, endDate = null) {
    // TODO: Implement AJAX call to fetch analytics data for selected time range
    // For now, refresh the page with query parameters
    const params = new URLSearchParams();
    params.set('analytics_range', timeRange);
    if (startDate) params.set('start_date', startDate);
    if (endDate) params.set('end_date', endDate);
    
    window.location.href = window.location.pathname + '?' + params.toString();
}

function exportAnalytics() {
    // TODO: Implement export functionality (CSV/Excel/PDF)
    alert('Export functionality will be implemented soon');
}

document.addEventListener('DOMContentLoaded', function() {
    @if(isset($systemAnalyticsData))
        // Initialize all charts
        initializeAnalyticsCharts();
    @endif
});

function initializeAnalyticsCharts() {
    @if(isset($systemAnalyticsData))
        // Budget Utilization Timeline Chart
        const budgetUtilizationData = @json($systemAnalyticsData['budget_utilization_timeline'] ?? []);
        if (budgetUtilizationData.length > 0 && typeof ApexCharts !== 'undefined') {
            const budgetUtilizationChart = new ApexCharts(document.querySelector("#budgetUtilizationChart"), {
                series: [{
                    name: 'Budget Utilization',
                    data: budgetUtilizationData.map(item => item.utilization)
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: { show: true }
                },
                xaxis: {
                    categories: budgetUtilizationData.map(item => item.month)
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(1) + '%';
                        }
                    }
                },
                colors: ['#667eea'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + '%';
                        }
                    }
                }
            });
            budgetUtilizationChart.render();
            analyticsCharts.budgetUtilization = budgetUtilizationChart;
        }

        // Budget Distribution by Province Chart
        const budgetByProvinceData = @json($systemAnalyticsData['budget_by_province'] ?? []);
        if (Object.keys(budgetByProvinceData).length > 0 && typeof ApexCharts !== 'undefined') {
            const budgetByProvinceChart = new ApexCharts(document.querySelector("#budgetByProvinceChart"), {
                series: Object.values(budgetByProvinceData),
                chart: {
                    type: 'bar',
                    height: 300,
                    horizontal: true
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        distributed: false
                    }
                },
                xaxis: {
                    categories: Object.keys(budgetByProvinceData)
                },
                colors: ['#667eea', '#11998e', '#f59e0b', '#ef4444', '#8b5cf6'],
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '₹' + val.toLocaleString('en-IN');
                        }
                    }
                }
            });
            budgetByProvinceChart.render();
            analyticsCharts.budgetByProvince = budgetByProvinceChart;
        }

        // Budget Distribution by Project Type Chart
        const budgetByProjectTypeData = @json($systemAnalyticsData['budget_by_project_type'] ?? []);
        if (Object.keys(budgetByProjectTypeData).length > 0 && typeof ApexCharts !== 'undefined') {
            const budgetByProjectTypeChart = new ApexCharts(document.querySelector("#budgetByProjectTypeChart"), {
                series: Object.values(budgetByProjectTypeData),
                chart: {
                    type: 'pie',
                    height: 300
                },
                labels: Object.keys(budgetByProjectTypeData),
                colors: ['#667eea', '#11998e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#10b981'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '₹' + val.toLocaleString('en-IN');
                        }
                    }
                }
            });
            budgetByProjectTypeChart.render();
            analyticsCharts.budgetByProjectType = budgetByProjectTypeChart;
        }

        // Expense Trends Over Time Chart
        const expenseTrendsData = @json($systemAnalyticsData['expense_trends'] ?? []);
        if (expenseTrendsData.length > 0 && typeof ApexCharts !== 'undefined') {
            const expenseTrendsChart = new ApexCharts(document.querySelector("#expenseTrendsChart"), {
                series: [{
                    name: 'Expenses',
                    data: expenseTrendsData.map(item => item.expenses)
                }],
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: true }
                },
                xaxis: {
                    categories: expenseTrendsData.map(item => item.month)
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return '₹' + val.toLocaleString('en-IN');
                        }
                    }
                },
                colors: ['#ef4444'],
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                markers: {
                    size: 5
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '₹' + val.toLocaleString('en-IN');
                        }
                    }
                }
            });
            expenseTrendsChart.render();
            analyticsCharts.expenseTrends = expenseTrendsChart;
        }

        // Approval Rate Trends Chart
        const approvalRateData = @json($systemAnalyticsData['approval_rate_trends'] ?? []);
        if (approvalRateData.length > 0 && typeof ApexCharts !== 'undefined') {
            const approvalRateChart = new ApexCharts(document.querySelector("#approvalRateChart"), {
                series: [{
                    name: 'Approval Rate',
                    data: approvalRateData.map(item => item.rate)
                }],
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: true }
                },
                xaxis: {
                    categories: approvalRateData.map(item => item.month)
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(1) + '%';
                        }
                    },
                    max: 100
                },
                colors: ['#10b981'],
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                markers: {
                    size: 5
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + '%';
                        }
                    }
                }
            });
            approvalRateChart.render();
            analyticsCharts.approvalRate = approvalRateChart;
        }

        // Report Submission Timeline Chart
        const reportSubmissionData = @json($systemAnalyticsData['report_submission_timeline'] ?? []);
        if (reportSubmissionData.length > 0 && typeof ApexCharts !== 'undefined') {
            const reportSubmissionChart = new ApexCharts(document.querySelector("#reportSubmissionChart"), {
                series: [
                    {
                        name: 'Approved',
                        data: reportSubmissionData.map(item => item.approved || 0)
                    },
                    {
                        name: 'Pending',
                        data: reportSubmissionData.map(item => item.pending || 0)
                    },
                    {
                        name: 'Reverted',
                        data: reportSubmissionData.map(item => item.reverted || 0)
                    }
                ],
                chart: {
                    type: 'area',
                    height: 300,
                    stacked: true,
                    toolbar: { show: true }
                },
                xaxis: {
                    categories: reportSubmissionData.map(item => item.month)
                },
                colors: ['#10b981', '#f59e0b', '#ef4444'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
                legend: {
                    position: 'bottom'
                }
            });
            reportSubmissionChart.render();
            analyticsCharts.reportSubmission = reportSubmissionChart;
        }

        // Province Performance Comparison Chart
        @php
            $provinceComparisonData = $systemAnalyticsData['province_comparison'] ?? [];
        @endphp
        const provinceComparisonData = @json($provinceComparisonData);
        if (Object.keys(provinceComparisonData).length > 0 && typeof ApexCharts !== 'undefined') {
            const provinces = Object.keys(provinceComparisonData);
            const provinceComparisonChart = new ApexCharts(document.querySelector("#provinceComparisonChart"), {
                series: [
                    {
                        name: 'Projects',
                        data: provinces.map(province => provinceComparisonData[province].projects || 0)
                    },
                    {
                        name: 'Budget',
                        data: provinces.map(province => (provinceComparisonData[province].budget || 0) / 100000) // Convert to lakhs
                    },
                    {
                        name: 'Expenses',
                        data: provinces.map(province => (provinceComparisonData[province].expenses || 0) / 100000)
                    },
                    {
                        name: 'Approval Rate %',
                        data: provinces.map(province => provinceComparisonData[province].approval_rate || 0)
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 400,
                    toolbar: { show: true }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4
                    }
                },
                xaxis: {
                    categories: provinces
                },
                yaxis: [
                    {
                        title: { text: 'Count/Amount (in Lakhs)' }
                    },
                    {
                        opposite: true,
                        title: { text: 'Approval Rate %' },
                        max: 100
                    }
                ],
                colors: ['#667eea', '#11998e', '#f59e0b', '#10b981'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val, opts) {
                            if (opts.seriesIndex === 3) {
                                return val.toFixed(1) + '%';
                            } else if (opts.seriesIndex === 1 || opts.seriesIndex === 2) {
                                return '₹' + val.toFixed(2) + ' L';
                            }
                            return val;
                        }
                    }
                }
            });
            provinceComparisonChart.render();
            analyticsCharts.provinceComparison = provinceComparisonChart;
        }
    @endif
}
</script>
@endpush