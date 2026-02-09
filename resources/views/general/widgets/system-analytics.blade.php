@php
    $analyticsData = $systemAnalyticsData ?? [];
    $timeRange = request('analytics_range', 30);
    $context = request('analytics_context', 'combined');

    $projectsByStatus = $analyticsData['projects_by_status'] ?? [];
    $reportsByStatus = $analyticsData['reports_by_status'] ?? [];
    $approvalRateTrends = $analyticsData['approval_rate_trends'] ?? [];
    $approvalRateTrendsCoordinator = $analyticsData['approval_rate_trends_coordinator'] ?? [];
    $approvalRateTrendsDirectTeam = $analyticsData['approval_rate_trends_direct_team'] ?? [];
    $approvalMovingAvg = $analyticsData['approval_moving_avg'] ?? [];
    $submissionRateTrends = $analyticsData['submission_rate_trends'] ?? [];
    $submissionRateTrendsCoordinator = $analyticsData['submission_rate_trends_coordinator'] ?? [];
    $submissionRateTrendsDirectTeam = $analyticsData['submission_rate_trends_direct_team'] ?? [];
    $submissionMovingAvg = $analyticsData['submission_moving_avg'] ?? [];
    $trendIndicators = $analyticsData['trend_indicators'] ?? [];
@endphp

{{-- System Analytics Widget --}}
<div class="card mb-4 widget-card" data-widget-id="system-analytics">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Analytics</h5>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="analyticsTimeRangeSelector" onchange="updateAnalyticsTimeRange()">
                <option value="7" {{ $timeRange == 7 ? 'selected' : '' }}>Last 7 Days</option>
                <option value="30" {{ $timeRange == 30 ? 'selected' : '' }}>Last 30 Days</option>
                <option value="90" {{ $timeRange == 90 ? 'selected' : '' }}>Last 3 Months</option>
                <option value="180" {{ $timeRange == 180 ? 'selected' : '' }}>Last 6 Months</option>
                <option value="365" {{ $timeRange == 365 ? 'selected' : '' }}>Last Year</option>
            </select>
            <select class="form-select form-select-sm" id="analyticsContextSelector" onchange="updateAnalyticsContext()">
                <option value="combined" {{ $context === 'combined' || !$context ? 'selected' : '' }}>Combined</option>
                <option value="coordinator_hierarchy" {{ $context === 'coordinator_hierarchy' ? 'selected' : '' }}>Coordinator Hierarchy</option>
                <option value="direct_team" {{ $context === 'direct_team' ? 'selected' : '' }}>Direct Team</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="system-analytics" title="Minimize">−</button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if(empty($analyticsData))
            <div class="text-center py-4">
                <p class="text-muted">No analytics data available</p>
            </div>
        @else
            {{-- Status Distribution Charts --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Projects by Status ({{ $context === 'coordinator_hierarchy' ? 'Coordinator Hierarchy' : ($context === 'direct_team' ? 'Direct Team' : 'Combined') }})</h6>
                        </div>
                        <div class="card-body">
                            @if(!empty($projectsByStatus))
                                <div id="analyticsProjectsStatusChart" style="min-height: 300px;"></div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <p class="mb-0">No project status data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Reports by Status ({{ $context === 'coordinator_hierarchy' ? 'Coordinator Hierarchy' : ($context === 'direct_team' ? 'Direct Team' : 'Combined') }})</h6>
                        </div>
                        <div class="card-body">
                            @if(!empty($reportsByStatus))
                                <div id="analyticsReportsStatusChart" style="min-height: 300px;"></div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <p class="mb-0">No report status data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trend Charts with Advanced Analysis --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Approval Rate Trends</h6>
                            @if(!empty($trendIndicators['approval_rate']))
                                @php
                                    $approvalTrend = $trendIndicators['approval_rate'];
                                    $trendColor = $approvalTrend['direction'] === 'up' ? 'text-success' : ($approvalTrend['direction'] === 'down' ? 'text-danger' : 'text-muted');
                                    $trendIcon = $approvalTrend['direction'] === 'up' ? 'trending-up' : ($approvalTrend['direction'] === 'down' ? 'trending-down' : 'minus');
                                    $trendSign = $approvalTrend['change'] > 0 ? '+' : '';
                                @endphp
                                <small class="{{ $trendColor }}">
                                    {{ $trendSign }}{{ number_format($approvalTrend['change'], 2) }}%
                                    ({{ $trendSign }}{{ number_format($approvalTrend['change_percent'], 1) }}%)
                                </small>
                            @endif
                        </div>
                        <div class="card-body">
                            @if(!empty($approvalRateTrends))
                                <div id="approvalRateTrendsChart" style="min-height: 300px;"></div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <p class="mb-0">No approval rate trend data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Report Submission Rate Trends</h6>
                            @if(!empty($trendIndicators['submissions']))
                                @php
                                    $submissionTrend = $trendIndicators['submissions'];
                                    $trendColor = $submissionTrend['direction'] === 'up' ? 'text-success' : ($submissionTrend['direction'] === 'down' ? 'text-danger' : 'text-muted');
                                    $trendIcon = $submissionTrend['direction'] === 'up' ? 'trending-up' : ($submissionTrend['direction'] === 'down' ? 'trending-down' : 'minus');
                                    $trendSign = $submissionTrend['change'] > 0 ? '+' : '';
                                @endphp
                                <small class="{{ $trendColor }}">
                                    {{ $trendSign }}{{ number_format($submissionTrend['change']) }}
                                    ({{ $trendSign }}{{ number_format($submissionTrend['change_percent'], 1) }}%)
                                </small>
                            @endif
                        </div>
                        <div class="card-body">
                            @if(!empty($submissionRateTrends))
                                <div id="submissionRateTrendsChart" style="min-height: 300px;"></div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <p class="mb-0">No submission rate trend data available</p>
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
function updateAnalyticsTimeRange() {
    const timeRange = document.getElementById('analyticsTimeRangeSelector').value;
    const context = document.getElementById('analyticsContextSelector').value;
    const url = new URL(window.location.href);
    url.searchParams.set('analytics_range', timeRange);
    url.searchParams.set('analytics_context', context);
    window.location.href = url.toString();
}

function updateAnalyticsContext() {
    const timeRange = document.getElementById('analyticsTimeRangeSelector').value;
    const context = document.getElementById('analyticsContextSelector').value;
    const url = new URL(window.location.href);
    url.searchParams.set('analytics_range', timeRange);
    url.searchParams.set('analytics_context', context);
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    @if(!empty($projectsByStatus))
        // Projects by Status Chart
        const analyticsProjectsStatusData = @json($projectsByStatus);
        if (Object.keys(analyticsProjectsStatusData).length > 0 && typeof ApexCharts !== 'undefined') {
            const analyticsProjectsStatusChart = new ApexCharts(document.querySelector("#analyticsProjectsStatusChart"), {
                series: Object.values(analyticsProjectsStatusData),
                chart: {
                    type: 'pie',
                    height: 300
                },
                labels: Object.keys(analyticsProjectsStatusData).map(status => status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())),
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
            analyticsProjectsStatusChart.render();
        }
    @endif

    @if(!empty($reportsByStatus))
        // Reports by Status Chart
        const analyticsReportsStatusData = @json($reportsByStatus);
        if (Object.keys(analyticsReportsStatusData).length > 0 && typeof ApexCharts !== 'undefined') {
            const analyticsReportsStatusChart = new ApexCharts(document.querySelector("#analyticsReportsStatusChart"), {
                series: Object.values(analyticsReportsStatusData),
                chart: {
                    type: 'pie',
                    height: 300
                },
                labels: Object.keys(analyticsReportsStatusData).map(status => status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())),
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
            analyticsReportsStatusChart.render();
        }
    @endif

    @if(!empty($approvalRateTrends))
        // Approval Rate Trends Chart with Advanced Features
        const approvalRateTrendsData = @json($approvalRateTrends);
        const approvalRateTrendsCoordinator = @json($approvalRateTrendsCoordinator ?? []);
        const approvalRateTrendsDirectTeam = @json($approvalRateTrendsDirectTeam ?? []);
        const approvalMovingAvg = @json($approvalMovingAvg ?? []);
        const isCombinedContext = {{ $context === 'combined' ? 'true' : 'false' }};

        if (approvalRateTrendsData.length > 0 && typeof ApexCharts !== 'undefined') {
            const approvalSeries = [];

            // Add main series based on context
            if (isCombinedContext && approvalRateTrendsCoordinator.length > 0 && approvalRateTrendsDirectTeam.length > 0) {
                // Multi-series comparison for combined context
                approvalSeries.push({
                    name: 'Combined',
                    data: approvalRateTrendsData.map(item => item.approval_rate),
                    type: 'line'
                });
                approvalSeries.push({
                    name: 'Coordinator Hierarchy',
                    data: approvalRateTrendsCoordinator,
                    type: 'line'
                });
                approvalSeries.push({
                    name: 'Direct Team',
                    data: approvalRateTrendsDirectTeam,
                    type: 'line'
                });
            } else {
                // Single series for non-combined context
                approvalSeries.push({
                    name: 'Approval Rate',
                    data: approvalRateTrendsData.map(item => item.approval_rate),
                    type: 'line'
                });
            }

            // Add moving average if available
            if (approvalMovingAvg.length > 0 && approvalMovingAvg.filter(v => v !== null).length > 0) {
                approvalSeries.push({
                    name: 'Moving Average (3-month)',
                    data: approvalMovingAvg,
                    type: 'line'
                });
            }

            const approvalRateTrendsChart = new ApexCharts(document.querySelector("#approvalRateTrendsChart"), {
                series: approvalSeries,
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: true },
                    zoom: { enabled: true }
                },
                xaxis: {
                    categories: approvalRateTrendsData.map(item => item.month)
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return val !== null ? val.toFixed(1) + '%' : '';
                        }
                    },
                    max: 100,
                    min: 0
                },
                colors: isCombinedContext && approvalRateTrendsCoordinator.length > 0
                    ? ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6']
                    : ['#10b981', '#8b5cf6'],
                stroke: {
                    curve: 'smooth',
                    width: isCombinedContext ? [3, 2, 2, 2] : [3, 2],
                    dashArray: isCombinedContext ? [0, 0, 0, 5] : [0, 5]
                },
                markers: {
                    size: [5, 4, 4, 0],
                    hover: { size: 7 }
                },
                legend: {
                    position: 'bottom',
                    show: true
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val) {
                            return val !== null ? val.toFixed(2) + '%' : 'N/A';
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 4
                }
            });
            approvalRateTrendsChart.render();
        }
    @endif

    @if(!empty($submissionRateTrends))
        // Submission Rate Trends Chart with Advanced Features
        const submissionRateTrendsData = @json($submissionRateTrends);
        const submissionRateTrendsCoordinator = @json($submissionRateTrendsCoordinator ?? []);
        const submissionRateTrendsDirectTeam = @json($submissionRateTrendsDirectTeam ?? []);
        const submissionMovingAvg = @json($submissionMovingAvg ?? []);
        const isCombinedContext = {{ $context === 'combined' ? 'true' : 'false' }};

        if (submissionRateTrendsData.length > 0 && typeof ApexCharts !== 'undefined') {
            const submissionSeries = [];

            // Add main series based on context
            if (isCombinedContext && submissionRateTrendsCoordinator.length > 0 && submissionRateTrendsDirectTeam.length > 0) {
                // Multi-series comparison for combined context
                submissionSeries.push({
                    name: 'Combined',
                    data: submissionRateTrendsData.map(item => item.submissions),
                    type: 'line'
                });
                submissionSeries.push({
                    name: 'Coordinator Hierarchy',
                    data: submissionRateTrendsCoordinator,
                    type: 'line'
                });
                submissionSeries.push({
                    name: 'Direct Team',
                    data: submissionRateTrendsDirectTeam,
                    type: 'line'
                });
            } else {
                // Single series for non-combined context
                submissionSeries.push({
                    name: 'Report Submissions',
                    data: submissionRateTrendsData.map(item => item.submissions),
                    type: 'line'
                });
            }

            // Add moving average if available
            if (submissionMovingAvg.length > 0 && submissionMovingAvg.filter(v => v !== null).length > 0) {
                submissionSeries.push({
                    name: 'Moving Average (3-month)',
                    data: submissionMovingAvg,
                    type: 'line'
                });
            }

            const submissionRateTrendsChart = new ApexCharts(document.querySelector("#submissionRateTrendsChart"), {
                series: submissionSeries,
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: true },
                    zoom: { enabled: true }
                },
                xaxis: {
                    categories: submissionRateTrendsData.map(item => item.month)
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return val !== null ? val.toLocaleString('en-IN') : '';
                        }
                    },
                    min: 0
                },
                colors: isCombinedContext && submissionRateTrendsCoordinator.length > 0
                    ? ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6']
                    : ['#3b82f6', '#8b5cf6'],
                stroke: {
                    curve: 'smooth',
                    width: isCombinedContext ? [3, 2, 2, 2] : [3, 2],
                    dashArray: isCombinedContext ? [0, 0, 0, 5] : [0, 5]
                },
                markers: {
                    size: [5, 4, 4, 0],
                    hover: { size: 7 }
                },
                legend: {
                    position: 'bottom',
                    show: true
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val) {
                            return val !== null ? val.toLocaleString('en-IN') + ' reports' : 'N/A';
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 4
                }
            });
            submissionRateTrendsChart.render();
        }
    @endif

    // Re-initialize feather icons after chart renders
    setTimeout(function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }, 500);
});
</script>
@endpush
