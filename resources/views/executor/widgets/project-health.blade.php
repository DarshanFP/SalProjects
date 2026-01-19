{{-- Project Health Widget - Dark Theme Compatible --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="activity" class="me-2"></i>
            Project Health Overview
        </h5>
        <div class="widget-drag-handle">
            <i data-feather="move" style="width: 16px; height: 16px;" class="text-muted"></i>
        </div>
    </div>
    <div class="card-body">
        @if(isset($projectHealthSummary) && $projectHealthSummary['total'] > 0)
            {{-- Health Distribution --}}
            <div class="row mb-4">
                <div class="col-md-4 text-center mb-3 mb-md-0">
                    <div class="p-3 bg-success bg-opacity-25 border border-success rounded">
                        <i data-feather="check-circle" class="text-success mb-2" style="width: 32px; height: 32px;"></i>
                        <h4 class="mb-0 text-white">{{ $projectHealthSummary['good'] ?? 0 }}</h4>
                        <small class="text-muted">Good</small>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3 mb-md-0">
                    <div class="p-3 bg-warning bg-opacity-25 border border-warning rounded">
                        <i data-feather="alert-triangle" class="text-warning mb-2" style="width: 32px; height: 32px;"></i>
                        <h4 class="mb-0 text-white">{{ $projectHealthSummary['warning'] ?? 0 }}</h4>
                        <small class="text-muted">Warning</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="p-3 bg-danger bg-opacity-25 border border-danger rounded">
                        <i data-feather="x-circle" class="text-danger mb-2" style="width: 32px; height: 32px;"></i>
                        <h4 class="mb-0 text-white">{{ $projectHealthSummary['critical'] ?? 0 }}</h4>
                        <small class="text-muted">Critical</small>
                    </div>
                </div>
            </div>

            {{-- Health Mini Chart --}}
            @if(isset($projects) && $projects->total() > 0 && isset($enhancedProjects))
                <div class="mb-3">
                    <h6 class="mb-3 text-center">Health Distribution</h6>
                    <div id="projectHealthChart" style="min-height: 200px;"></div>
                </div>

                {{-- Health Breakdown --}}
                <div class="mt-3 pt-3 border-top border-secondary">
                    <h6 class="mb-3">Health Factors Overview</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Projects with Budget Issues</small>
                            <strong class="text-warning">
                                {{ collect($enhancedProjects)->filter(function($meta) {
                                    return ($meta['utilization_percent'] ?? 0) > 75;
                                })->count() }}
                            </strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Projects Needing Reports</small>
                            <strong class="text-danger">
                                {{ collect($enhancedProjects)->filter(function($meta) {
                                    return !isset($meta['last_report_date']) || is_null($meta['last_report_date']);
                                })->count() }}
                            </strong>
                        </div>
                    </div>
                </div>
            @endif

            {{-- View All Link --}}
            <div class="mt-3 pt-3 border-top border-secondary text-center">
                <a href="{{ route('executor.dashboard') }}?sort_by=health" class="text-info small">
                    View all projects with health details →
                </a>
            </div>
        @else
            <div class="text-center py-4">
                <i data-feather="inbox" class="text-muted" style="width: 48px; height: 48px;"></i>
                <p class="text-muted mt-3 mb-0">No project health data available</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize health chart if data available
    if (typeof ApexCharts !== 'undefined' && document.querySelector("#projectHealthChart")) {
        const healthSummary = @json($projectHealthSummary ?? []);
        
        if (healthSummary.total > 0) {
            const healthChartOptions = {
                series: [healthSummary.good || 0, healthSummary.warning || 0, healthSummary.critical || 0],
                chart: {
                    type: 'donut',
                    height: 200,
                    foreColor: '#d0d6e1',
                    background: 'transparent'
                },
                labels: ['Good', 'Warning', 'Critical'],
                colors: ['#05a34a', '#fbbc06', '#ff3366'],
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
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Projects',
                                    formatter: function() {
                                        return (healthSummary.total || 0).toString();
                                    },
                                    color: '#d0d6e1'
                                }
                            }
                        }
                    }
                }
            };

            const healthChart = new ApexCharts(document.querySelector("#projectHealthChart"), healthChartOptions);
            healthChart.render();
        }
    }
});
</script>
@endpush
