@php
    use App\Models\Reports\Monthly\DPReport;
    use App\Constants\ProjectStatus;
@endphp
{{-- Center Performance Comparison Widget --}}
<div class="card mb-4 widget-card" data-widget-id="center-comparison">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Center Performance Comparison</h5>
        <div>
            <select class="form-select form-select-sm d-inline-block" id="centerComparisonMetric" style="width: auto;">
                <option value="projects">Projects</option>
                <option value="budget">Budget</option>
                <option value="expenses">Expenses</option>
                <option value="utilization">Utilization</option>
                <option value="approval_rate">Approval Rate</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle ms-2" data-widget="center-comparison" title="Minimize">−</button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if(empty($centerComparison))
            <div class="text-center py-4">
                <p class="text-muted">No center data available</p>
            </div>
        @else
            {{-- Comparison Charts --}}
            <div class="row mb-4">
                {{-- Projects by Center Chart --}}
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Projects by Center</h6>
                        </div>
                        <div class="card-body">
                            <div id="projectsByCenterChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>

                {{-- Budget by Center Chart --}}
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Budget Allocation by Center</h6>
                        </div>
                        <div class="card-body">
                            <div id="budgetByCenterChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Comparison Chart --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Performance Comparison (All Metrics)</h6>
                        </div>
                        <div class="card-body">
                            <div id="performanceComparisonChart" style="min-height: 400px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Center Ranking Table --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Center Performance Ranking</h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-sort="overall">Overall</button>
                        <button type="button" class="btn btn-outline-secondary" data-sort="projects">Projects</button>
                        <button type="button" class="btn btn-outline-secondary" data-sort="budget">Budget</button>
                        <button type="button" class="btn btn-outline-secondary" data-sort="utilization">Utilization</button>
                        <button type="button" class="btn btn-outline-secondary" data-sort="approval_rate">Approval Rate</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="centerRankingTable">
                            <thead>
                                <tr>
                                    <th width="60">Rank</th>
                                    <th>Center</th>
                                    <th class="text-end">Projects</th>
                                    <th class="text-end">Budget</th>
                                    <th class="text-end">Expenses</th>
                                    <th class="text-end">Utilization %</th>
                                    <th class="text-end">Reports</th>
                                    <th class="text-end">Approval Rate</th>
                                    <th class="text-end">Performance Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Calculate performance scores and sort
                                    $rankedCenters = collect($centerComparison)->map(function($center, $name) {
                                        $score = 0;
                                        // Projects score (weight: 20%)
                                        $score += min(($center['projects'] ?? 0) / 50, 1) * 20;
                                        // Budget utilization score (weight: 30%) - lower is better
                                        $util = $center['utilization'] ?? 0;
                                        $score += (1 - min($util / 100, 1)) * 30;
                                        // Approval rate score (weight: 30%)
                                        $score += (($center['approval_rate'] ?? 0) / 100) * 30;
                                        // Reports count score (weight: 20%)
                                        $score += min(($center['reports'] ?? 0) / 100, 1) * 20;
                                        return array_merge($center, ['name' => $name, 'performance_score' => $score]);
                                    })->sortByDesc('performance_score')->values();
                                @endphp
                                @foreach($rankedCenters as $index => $center)
                                    @php
                                        $rank = $index + 1;
                                        $rankBadge = $rank <= 3 ? ['1' => 'bg-warning', '2' => 'bg-secondary', '3' => 'bg-info'][$rank] : 'bg-light text-dark';
                                        $utilClass = ($center['utilization'] ?? 0) > 80 ? 'danger' : (($center['utilization'] ?? 0) > 60 ? 'warning' : 'success');
                                        $approvalClass = ($center['approval_rate'] ?? 0) >= 80 ? 'success' : (($center['approval_rate'] ?? 0) >= 60 ? 'warning' : 'danger');
                                        $scoreClass = ($center['performance_score'] ?? 0) >= 70 ? 'success' : (($center['performance_score'] ?? 0) >= 50 ? 'warning' : 'danger');
                                    @endphp
                                    <tr class="center-row"
                                        data-projects="{{ $center['projects'] ?? 0 }}"
                                        data-budget="{{ $center['budget'] ?? 0 }}"
                                        data-utilization="{{ $center['utilization'] ?? 0 }}"
                                        data-approval-rate="{{ $center['approval_rate'] ?? 0 }}"
                                        data-performance-score="{{ $center['performance_score'] ?? 0 }}">
                                        <td>
                                            <span class="badge {{ $rankBadge }}">#{{ $rank }}</span>
                                            @if($rank <= 3)
                                                <span class="text-warning">★</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $center['name'] }}</strong></td>
                                        <td class="text-end">{{ $center['projects'] ?? 0 }}</td>
                                        <td class="text-end">{{ format_indian_currency($center['budget'] ?? 0) }}</td>
                                        <td class="text-end">{{ format_indian_currency($center['expenses'] ?? 0) }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-{{ $utilClass }}">{{ format_indian_percentage($center['utilization'] ?? 0, 1) }}</span>
                                        </td>
                                        <td class="text-end">{{ $center['reports'] ?? 0 }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-{{ $approvalClass }}">{{ format_indian_percentage($center['approval_rate'] ?? 0, 1) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-{{ $scoreClass }}">
                                                {{ format_indian($center['performance_score'] ?? 0, 1) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Top & Bottom Performers Summary --}}
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card bg-success bg-opacity-10 border-success">
                        <div class="card-header bg-success bg-opacity-25">
                            <h6 class="mb-0 text-success">Top 3 Performing Centers</h6>
                        </div>
                        <div class="card-body">
                            @foreach($rankedCenters->take(3) as $index => $center)
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div>
                                        <strong>#{{ $index + 1 }} {{ $center['name'] }}</strong>
                                        <br>
                                        <small class="text-muted">Score: {{ format_indian($center['performance_score'] ?? 0, 1) }}</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">{{ $center['projects'] ?? 0 }} Projects</span>
                                        <br>
                                        <span class="badge bg-info">{{ format_indian_percentage($center['approval_rate'] ?? 0, 1) }} Approval</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-warning bg-opacity-10 border-warning">
                        <div class="card-header bg-warning bg-opacity-25">
                            <h6 class="mb-0 text-warning">Centers Needing Attention</h6>
                        </div>
                        <div class="card-body">
                            @php
                                $needingAttention = $rankedCenters->filter(function($center) {
                                    return ($center['performance_score'] ?? 0) < 50 ||
                                           ($center['approval_rate'] ?? 0) < 60 ||
                                           ($center['utilization'] ?? 0) > 90;
                                })->take(3);
                            @endphp
                            @forelse($needingAttention as $center)
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div>
                                        <strong>{{ $center['name'] }}</strong>
                                        <br>
                                        <small class="text-muted">Score: {{ format_indian($center['performance_score'] ?? 0, 1) }}</small>
                                    </div>
                                    <div class="text-end">
                                        @if(($center['approval_rate'] ?? 0) < 60)
                                            <span class="badge bg-danger">Low Approval</span>
                                        @endif
                                        @if(($center['utilization'] ?? 0) > 90)
                                            <span class="badge bg-danger">High Utilization</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">All centers performing well!</p>
                            @endforelse
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

    // Initialize charts if ApexCharts is available
    if (typeof ApexCharts !== 'undefined') {
        initializeCenterComparisonCharts();
    }

    // Sorting buttons
    document.querySelectorAll('[data-sort]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-sort]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const sortBy = this.dataset.sort;
            sortCenterTable(sortBy);
        });
    });

    // Metric filter
    const metricFilter = document.getElementById('centerComparisonMetric');
    if (metricFilter) {
        metricFilter.addEventListener('change', function() {
            updateComparisonChart(this.value);
        });
    }
});

const darkThemeColors = {
    primary: '#6571ff',
    success: '#05a34a',
    warning: '#fbbc06',
    danger: '#ff3366',
    info: '#66d1d1',
    colors: ['#6571ff', '#05a34a', '#fbbc06', '#ff3366', '#66d1d1', '#ec4899', '#10b981', '#3b82f6']
};

let projectsByCenterChart = null;
let budgetByCenterChart = null;
let performanceComparisonChart = null;

function initializeCenterComparisonCharts() {
    const centerData = @json($centerComparison ?? []);

    if (!centerData || Object.keys(centerData).length === 0) return;

    const centers = Object.keys(centerData);
    const centerNames = centers.map(c => c.length > 20 ? c.substring(0, 20) + '...' : c);

    // Projects by Center (Bar Chart)
    if (document.querySelector("#projectsByCenterChart")) {
        projectsByCenterChart = new ApexCharts(document.querySelector("#projectsByCenterChart"), {
            series: [{
                name: 'Projects',
                data: centers.map(c => centerData[c].projects ?? 0)
            }],
            chart: {
                type: 'bar',
                height: 300,
                foreColor: '#d0d6e1',
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true
            },
            xaxis: {
                categories: centerNames,
                labels: { style: { colors: '#d0d6e1' } }
            },
            yaxis: {
                labels: { style: { colors: '#d0d6e1' } }
            },
            colors: [darkThemeColors.primary],
            tooltip: {
                theme: 'dark'
            }
        });
        projectsByCenterChart.render();
    }

    // Budget by Center (Bar Chart)
    if (document.querySelector("#budgetByCenterChart")) {
        budgetByCenterChart = new ApexCharts(document.querySelector("#budgetByCenterChart"), {
            series: [{
                name: 'Budget',
                data: centers.map(c => centerData[c].budget ?? 0)
            }],
            chart: {
                type: 'bar',
                height: 300,
                foreColor: '#d0d6e1',
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return 'Rs. ' + (val / 1000).toFixed(0) + 'K';
                }
            },
            xaxis: {
                categories: centerNames,
                labels: { style: { colors: '#d0d6e1' } }
            },
            yaxis: {
                labels: {
                    style: { colors: '#d0d6e1' },
                    formatter: function(val) {
                        return 'Rs. ' + (val / 1000).toFixed(0) + 'K';
                    }
                }
            },
            colors: [darkThemeColors.info],
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(val) {
                        return 'Rs. ' + val.toLocaleString('en-IN');
                    }
                }
            }
        });
        budgetByCenterChart.render();
    }

    // Performance Comparison (Grouped Bar Chart)
    if (document.querySelector("#performanceComparisonChart")) {
        performanceComparisonChart = new ApexCharts(document.querySelector("#performanceComparisonChart"), {
            series: [
                {
                    name: 'Projects',
                    data: centers.map(c => centerData[c].projects ?? 0)
                },
                {
                    name: 'Budget (K)',
                    data: centers.map(c => Math.round((centerData[c].budget ?? 0) / 1000))
                },
                {
                    name: 'Utilization %',
                    data: centers.map(c => Math.round(centerData[c].utilization ?? 0))
                },
                {
                    name: 'Approval Rate %',
                    data: centers.map(c => Math.round(centerData[c].approval_rate ?? 0))
                }
            ],
            chart: {
                type: 'bar',
                height: 400,
                foreColor: '#d0d6e1',
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: centerNames,
                labels: {
                    style: { colors: '#d0d6e1' },
                    rotate: -45,
                    rotateAlways: true
                }
            },
            yaxis: {
                labels: { style: { colors: '#d0d6e1' } }
            },
            colors: [darkThemeColors.primary, darkThemeColors.info, darkThemeColors.warning, darkThemeColors.success],
            legend: {
                position: 'top',
                labels: { colors: '#d0d6e1' }
            },
            tooltip: {
                theme: 'dark'
            }
        });
        performanceComparisonChart.render();
    }
}

function sortCenterTable(sortBy) {
    const tbody = document.querySelector('#centerRankingTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr.center-row'));

    rows.sort((a, b) => {
        const aVal = parseFloat(a.dataset[sortBy === 'overall' ? 'performanceScore' : sortBy.replace('_', '-')] || 0);
        const bVal = parseFloat(b.dataset[sortBy === 'overall' ? 'performanceScore' : sortBy.replace('_', '-')] || 0);
        return bVal - aVal; // Descending order
    });

    rows.forEach((row, index) => {
        const rankCell = row.querySelector('td:first-child');
        const rank = index + 1;
        const rankBadge = rank <= 3 ? ['1' => 'bg-warning', '2' => 'bg-secondary', '3' => 'bg-info'][rank] : 'bg-light text-dark';
        rankCell.innerHTML = `<span class="badge ${rankBadge}">#${rank}</span>`;
        if (rank <= 3) {
            rankCell.innerHTML += ' <span class="text-warning">★</span>';
        }
        tbody.appendChild(row);
    });
}

function updateComparisonChart(metric) {
    // Update the performance comparison chart based on selected metric
    // This can be enhanced to show different metrics
    // Metric switching logic can be implemented here
}
</script>
@endpush
