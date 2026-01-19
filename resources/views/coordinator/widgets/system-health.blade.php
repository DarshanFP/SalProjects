@php
    $healthData = $systemHealthData ?? [];
    $overallScore = $healthData['overall_score'] ?? 0;
    $healthLevel = $healthData['health_level'] ?? 'poor';
    $factors = $healthData['factors'] ?? [];
    $alerts = $healthData['alerts'] ?? [];
    $trends = $healthData['trends'] ?? [];
    $summary = $healthData['summary'] ?? [];

    $healthColor = match($healthLevel) {
        'excellent' => 'success',
        'good' => 'info',
        'fair' => 'warning',
        default => 'danger',
    };
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Health Indicators</h5>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-secondary" onclick="refreshHealth()">
                Refresh
            </button>
        </div>
    </div>
    <div class="card-body">
        @if(empty($healthData) || empty($overallScore))
            {{-- Empty State --}}
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="feather icon-activity" style="font-size: 48px; color: #ccc;"></i>
                </div>
                <h5 class="text-muted">No Health Data Available</h5>
                <p class="text-muted">Health indicators will be calculated once there is system activity.</p>
            </div>
        @else
            {{-- Overall Health Score --}}
            <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-{{ $healthColor }} text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Overall System Health Score</h5>
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h1 class="display-1 mb-0">{{ $overallScore }}</h1>
                                <h4>/ 100</h4>
                            </div>
                            <div class="col-md-8">
                                <h3 class="mb-3">Status: {{ ucfirst($healthLevel) }}</h3>
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar bg-light text-dark" 
                                         role="progressbar" 
                                         style="width: {{ $overallScore }}%"
                                         aria-valuenow="{{ $overallScore }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <strong>{{ $overallScore }}%</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Health Alerts --}}
        @if(count($alerts) > 0)
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-{{ collect($alerts)->contains('type', 'critical') ? 'danger' : 'warning' }} text-white">
                        <h6 class="mb-0">System Alerts ({{ count($alerts) }})</h6>
                    </div>
                    <div class="card-body">
                        @foreach($alerts as $alert)
                            <div class="alert alert-{{ $alert['color'] }} alert-dismissible fade show" role="alert">
                                <strong>{{ ucfirst($alert['type']) }}:</strong> {{ $alert['message'] }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Key Indicators --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Budget Utilization</h6>
                    </div>
                    <div class="card-body">
                        <h3 class="mb-0">{{ format_indian_percentage($factors['budget_utilization'] ?? 0, 1) }}</h3>
                        <div class="progress mt-2" style="height: 25px;">
                            <div class="progress-bar {{ ($factors['budget_utilization'] ?? 0) >= 90 ? 'bg-danger' : (($factors['budget_utilization'] ?? 0) >= 75 ? 'bg-warning' : 'bg-success') }}" 
                                 style="width: {{ min($factors['budget_utilization'] ?? 0, 100) }}%">
                                {{ format_indian_percentage($factors['budget_utilization'] ?? 0, 1) }}
                            </div>
                        </div>
                        <small class="text-muted">Optimal: 70-80%</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Approval Rate</h6>
                    </div>
                    <div class="card-body">
                        <h3 class="mb-0">{{ format_indian_percentage($factors['approval_rate'] ?? 0, 1) }}</h3>
                        <div class="progress mt-2" style="height: 25px;">
                            <div class="progress-bar {{ ($factors['approval_rate'] ?? 0) >= 80 ? 'bg-success' : (($factors['approval_rate'] ?? 0) >= 60 ? 'bg-warning' : 'bg-danger') }}" 
                                 style="width: {{ min($factors['approval_rate'] ?? 0, 100) }}%">
                                {{ format_indian_percentage($factors['approval_rate'] ?? 0, 1) }}
                            </div>
                        </div>
                        <small class="text-muted">Target: > 80%</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Avg Processing Time</h6>
                    </div>
                    <div class="card-body">
                        <h3 class="mb-0">{{ format_indian($factors['avg_processing_time'] ?? 0, 1) }} days</h3>
                        <div class="progress mt-2" style="height: 25px;">
                            <div class="progress-bar {{ ($factors['avg_processing_time'] ?? 0) > 10 ? 'bg-danger' : (($factors['avg_processing_time'] ?? 0) > 5 ? 'bg-warning' : 'bg-success') }}" 
                                 style="width: {{ min(($factors['avg_processing_time'] ?? 0) * 10, 100) }}%">
                                {{ format_indian($factors['avg_processing_time'] ?? 0, 1) }}
                            </div>
                        </div>
                        <small class="text-muted">Target: < 5 days</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Completion Rate</h6>
                    </div>
                    <div class="card-body">
                        <h3 class="mb-0">{{ format_indian_percentage($factors['completion_rate'] ?? 0, 1) }}</h3>
                        <div class="progress mt-2" style="height: 25px;">
                            <div class="progress-bar {{ ($factors['completion_rate'] ?? 0) >= 70 ? 'bg-success' : (($factors['completion_rate'] ?? 0) >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                 style="width: {{ min($factors['completion_rate'] ?? 0, 100) }}%">
                                {{ format_indian_percentage($factors['completion_rate'] ?? 0, 1) }}
                            </div>
                        </div>
                        <small class="text-muted">Target: > 70%</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Submission Rate</h6>
                    </div>
                    <div class="card-body">
                        <h3 class="mb-0">{{ format_indian_percentage($factors['submission_rate'] ?? 0, 1) }}</h3>
                        <small class="text-muted">
                            @if(($factors['submission_rate'] ?? 0) > 0)
                                <span class="text-success">↑ Increased</span>
                            @elseif(($factors['submission_rate'] ?? 0) < 0)
                                <span class="text-danger">↓ Decreased</span>
                            @else
                                <span class="text-muted">→ No change</span>
                            @endif
                            from last month
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Activity Rate</h6>
                    </div>
                    <div class="card-body">
                        <h3 class="mb-0">{{ format_indian_percentage($factors['activity_rate'] ?? 0, 1) }}</h3>
                        <div class="progress mt-2" style="height: 25px;">
                            <div class="progress-bar {{ ($factors['activity_rate'] ?? 0) >= 70 ? 'bg-success' : (($factors['activity_rate'] ?? 0) >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                 style="width: {{ min($factors['activity_rate'] ?? 0, 100) }}%">
                                {{ format_indian_percentage($factors['activity_rate'] ?? 0, 1) }}
                            </div>
                        </div>
                        <small class="text-muted">Active users in last 30 days</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Health Trends Chart --}}
        @if(count($trends) > 0)
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Health Score Trend (Last 6 Months)</h6>
                    </div>
                    <div class="card-body">
                        <div id="healthTrendsChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Summary Stats --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">System Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h5 class="mb-0">{{ $summary['total_projects'] ?? 0 }}</h5>
                                <small class="text-muted">Total Projects</small>
                            </div>
                            <div class="col-md-3">
                                <h5 class="mb-0">{{ $summary['total_reports'] ?? 0 }}</h5>
                                <small class="text-muted">Total Reports</small>
                            </div>
                            <div class="col-md-3">
                                <h5 class="mb-0 text-warning">{{ $summary['pending_reports'] ?? 0 }}</h5>
                                <small class="text-muted">Pending Reports</small>
                            </div>
                            <div class="col-md-3">
                                <h5 class="mb-0">{{ format_indian_currency($summary['total_budget'] ?? 0, 2) }}</h5>
                                <small class="text-muted">Total Budget</small>
                            </div>
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
    // Health Trends Chart (Line Chart)
    @if(count($trends) > 0)
    var healthTrendsOptions = {
        series: [{
            name: 'Health Score',
            data: @json(array_column($trends, 'score'))
        }],
        chart: {
            type: 'line',
            height: 300,
            toolbar: {
                show: true
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val.toFixed(0);
            }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: @json(array_column($trends, 'month')),
        },
        yaxis: {
            min: 0,
            max: 100,
            title: {
                text: 'Health Score'
            }
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
        markers: {
            size: 5,
            hover: {
                size: 7
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val.toFixed(1) + " / 100";
                }
            }
        }
    };
    var healthTrendsChart = new ApexCharts(document.querySelector("#healthTrendsChart"), healthTrendsOptions);
    healthTrendsChart.render();
    @endif

    // Refresh Health
    window.refreshHealth = function() {
        window.location.reload();
    };
});
</script>
@endpush