@php
    $comparisonData = $provinceComparisonData ?? [];
    $provincePerformance = $comparisonData['province_performance'] ?? [];
    $rankings = $comparisonData['rankings'] ?? [];
    $summary = $comparisonData['summary'] ?? [];
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Province Performance Comparison</h5>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-secondary" onclick="exportProvinceComparison()">
                Export
            </button>
        </div>
    </div>
    <div class="card-body">
        @if(empty($provincePerformance) || count($provincePerformance) === 0)
            {{-- Empty State --}}
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="feather icon-map" style="font-size: 48px; color: #ccc;"></i>
                </div>
                <h5 class="text-muted">No Province Data Available</h5>
                <p class="text-muted">There are no provinces with performance data yet.</p>
            </div>
        @else
            {{-- Summary Cards --}}
            <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Provinces</h6>
                        <h4 class="mb-0">{{ $summary['total_provinces'] ?? count($provincePerformance) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Top Performer</h6>
                        <h5 class="mb-0">{{ $summary['top_performer'] ?? 'N/A' }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Highest Budget</h6>
                        <h5 class="mb-0">{{ $summary['highest_budget'] ?? 'N/A' }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Most Utilized</h6>
                        <h5 class="mb-0">{{ $summary['most_utilized'] ?? 'N/A' }}</h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- Comparison Chart --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Province Comparison</h6>
                    </div>
                    <div class="card-body">
                        <div id="provinceComparisonChart" style="min-height: 400px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Performance Table --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Province Performance Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th>Rank</th>
                                        <th>Province</th>
                                        <th>Projects</th>
                                        <th>Reports</th>
                                        <th>Budget</th>
                                        <th>Expenses</th>
                                        <th>Utilization</th>
                                        <th>Approval Rate</th>
                                        <th>Avg Processing Time</th>
                                        <th>Provincials</th>
                                        <th>Users</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($provincePerformance as $province => $data)
                                        @php
                                            $rank = $loop->iteration;
                                            $isTopPerformer = ($rankings['by_approval_rate'] ?? collect())->first() === $province;
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($rank <= 3)
                                                    <span class="badge badge-{{ $rank === 1 ? 'warning' : ($rank === 2 ? 'info' : 'secondary') }}">
                                                        #{{ $rank }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">#{{ $rank }}</span>
                                                @endif
                                            </td>
                                            <td><strong>{{ $province }}</strong></td>
                                            <td>
                                                <span class="badge badge-info">{{ $data['projects'] }}</span>
                                                <small class="text-muted">({{ $data['approved_projects'] }} approved)</small>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">{{ $data['reports'] }}</span>
                                                <small class="text-muted">({{ $data['approved_reports'] }} approved)</small>
                                            </td>
                                            <td>{{ format_indian_currency($data['budget'], 2) }}</td>
                                            <td>{{ format_indian_currency($data['expenses'], 2) }}</td>
                                            <td>
                                                <div class="progress" style="height: 20px; width: 100px;">
                                                    <div class="progress-bar {{ $data['utilization'] >= 90 ? 'bg-danger' : ($data['utilization'] >= 75 ? 'bg-warning' : 'bg-success') }}"
                                                         style="width: {{ min($data['utilization'], 100) }}%"
                                                         title="{{ format_indian_percentage($data['utilization'], 1) }}">
                                                        {{ format_indian_percentage($data['utilization'], 1) }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px; width: 100px;">
                                                    <div class="progress-bar {{ $data['approval_rate'] >= 80 ? 'bg-success' : ($data['approval_rate'] >= 60 ? 'bg-warning' : 'bg-danger') }}"
                                                         style="width: {{ min($data['approval_rate'], 100) }}%"
                                                         title="{{ format_indian_percentage($data['approval_rate'], 1) }}">
                                                        {{ format_indian_percentage($data['approval_rate'], 1) }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $data['avg_processing_time'] > 10 ? 'danger' : ($data['avg_processing_time'] > 5 ? 'warning' : 'success') }}">
                                                    {{ $data['avg_processing_time'] }} days
                                                </span>
                                            </td>
                                            <td><span class="badge badge-secondary">{{ $data['provincials_count'] }}</span></td>
                                            <td><span class="badge badge-dark">{{ $data['users_count'] }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted">No data available</td>
                                        </tr>
                                    @endforelse
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
    // Province Comparison Chart (Grouped Bar Chart)
    @if(count($provincePerformance) > 0)
    var provinceComparisonOptions = {
        series: [
            {
                name: 'Budget',
                data: @json(array_column($provincePerformance, 'budget'))
            },
            {
                name: 'Expenses',
                data: @json(array_column($provincePerformance, 'expenses'))
            },
            {
                name: 'Approval Rate',
                data: @json(array_map(function($v) { return $v / 100 * 1000000; }, array_column($provincePerformance, 'approval_rate')))
            }
        ],
        chart: {
            type: 'bar',
            height: 400,
            stacked: false,
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: @json(array_keys($provincePerformance)),
        },
        yaxis: {
            title: {
                text: 'Amount (₹)'
            }
        },
        fill: {
            opacity: 1
        },
        colors: ['#3b82f6', '#10b981', '#f59e0b'],
        legend: {
            position: 'top',
            horizontalAlign: 'left',
        },
        tooltip: {
            y: {
                formatter: function (val, opts) {
                    if (opts.seriesIndex === 2) {
                        // Approval rate - convert back
                        return (val / 1000000 * 100).toFixed(1) + '%';
                    }
                    return "₹" + val.toLocaleString('en-IN', {minimumFractionDigits: 2});
                }
            }
        }
    };
    var provinceComparisonChart = new ApexCharts(document.querySelector("#provinceComparisonChart"), provinceComparisonOptions);
    provinceComparisonChart.render();
    @endif

    // Export Province Comparison
    window.exportProvinceComparison = function() {
        alert('Export functionality will be implemented soon.');
    };
});
</script>
@endpush
