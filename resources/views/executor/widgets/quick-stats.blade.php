{{-- Quick Stats Widget - Dark Theme Compatible --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="bar-chart" class="me-2"></i>
            Quick Stats
        </h5>
        <div class="widget-drag-handle">
            <i data-feather="move" style="width: 16px; height: 16px;" class="text-muted"></i>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Total Projects --}}
            <div class="col-6 col-md-4">
                <div class="card bg-primary bg-opacity-25 border-primary h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted d-block">Total Projects</small>
                                <h4 class="mb-0 text-white">{{ $quickStats['total_projects'] ?? 0 }}</h4>
                                @if(isset($quickStats['projects_trend']))
                                    @php
                                        $trend = $quickStats['projects_trend'];
                                        $trendIcon = $trend > 0 ? 'trending-up' : ($trend < 0 ? 'trending-down' : 'minus');
                                        $trendColor = $trend > 0 ? 'text-success' : ($trend < 0 ? 'text-danger' : 'text-muted');
                                    @endphp
                                    <small class="{{ $trendColor }}">
                                        <i data-feather="{{ $trendIcon }}" style="width: 12px; height: 12px;"></i>
                                        {{ $trend > 0 ? '+' : '' }}{{ $trend }} vs last month
                                    </small>
                                @endif
                            </div>
                            <div class="text-primary">
                                <i data-feather="folder" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Projects --}}
            <div class="col-6 col-md-4">
                <div class="card bg-success bg-opacity-25 border-success h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted d-block">Active Projects</small>
                                <h4 class="mb-0 text-white">{{ $quickStats['active_projects'] ?? 0 }}</h4>
                                <small class="text-muted">
                                    @if(isset($quickStats['total_projects']) && $quickStats['total_projects'] > 0)
                                        {{ format_indian_percentage(($quickStats['active_projects'] / $quickStats['total_projects']) * 100, 1) }} of total
                                    @else
                                        0%
                                    @endif
                                </small>
                            </div>
                            <div class="text-success">
                                <i data-feather="check-circle" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Reports --}}
            <div class="col-6 col-md-4">
                <div class="card bg-info bg-opacity-25 border-info h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted d-block">Total Reports</small>
                                <h4 class="mb-0 text-white">{{ $quickStats['total_reports'] ?? 0 }}</h4>
                                <small class="text-muted">
                                    @if(isset($quickStats['approved_reports']))
                                        {{ $quickStats['approved_reports'] }} approved
                                    @endif
                                </small>
                            </div>
                            <div class="text-info">
                                <i data-feather="file-text" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Approval Rate --}}
            <div class="col-6 col-md-4">
                <div class="card bg-success bg-opacity-25 border-success h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted d-block">Approval Rate</small>
                                <h4 class="mb-0 text-white">{{ format_indian_percentage($quickStats['approval_rate'] ?? 0, 1) }}</h4>
                                <small class="text-muted">
                                    @if(isset($quickStats['approved_reports']) && isset($quickStats['total_reports']))
                                        {{ $quickStats['approved_reports'] }}/{{ $quickStats['total_reports'] }} reports
                                    @endif
                                </small>
                            </div>
                            <div class="text-success">
                                <i data-feather="target" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Budget Utilization --}}
            <div class="col-6 col-md-4">
                <div class="card bg-warning bg-opacity-25 border-warning h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted d-block">Budget Utilization</small>
                                <h4 class="mb-0 text-white">{{ format_indian_percentage($quickStats['budget_utilization'] ?? 0, 1) }}</h4>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar 
                                        {{ ($quickStats['budget_utilization'] ?? 0) > 90 ? 'bg-danger' : (($quickStats['budget_utilization'] ?? 0) > 75 ? 'bg-warning' : 'bg-success') }}" 
                                        role="progressbar" 
                                        style="width: {{ min($quickStats['budget_utilization'] ?? 0, 100) }}%"
                                        aria-valuenow="{{ $quickStats['budget_utilization'] ?? 0 }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            <div class="text-warning">
                                <i data-feather="pie-chart" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Average Project Budget --}}
            <div class="col-6 col-md-4">
                <div class="card bg-secondary bg-opacity-25 border-secondary h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted d-block">Avg Project Budget</small>
                                <h4 class="mb-0 text-white">{{ format_indian_currency($quickStats['average_project_budget'] ?? 0, 0) }}</h4>
                                <small class="text-muted">
                                    @if(isset($quickStats['active_projects']))
                                        Across {{ $quickStats['active_projects'] }} active projects
                                    @endif
                                </small>
                            </div>
                            <div class="text-secondary">
                                <i data-feather="dollar-sign" style="width: 32px; height: 32px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Info --}}
        @if(isset($quickStats['new_projects_this_month']))
            <div class="mt-3 pt-3 border-top border-secondary">
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <small class="text-muted d-block">New This Month</small>
                        <strong class="text-info">{{ $quickStats['new_projects_this_month'] }} projects</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Total Budget</small>
                        <strong class="text-white">{{ format_indian_currency($quickStats['total_budget'] ?? 0, 0) }}</strong>
                    </div>
                </div>
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
});
</script>
@endpush
