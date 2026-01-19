@php
    $healthData = $systemHealthData ?? [];
    $coordinatorHierarchy = $healthData['coordinator_hierarchy'] ?? [];
    $directTeam = $healthData['direct_team'] ?? [];
    $combined = $healthData['combined'] ?? [];
@endphp

{{-- System Health Widget --}}
<div class="card mb-4 widget-card" data-widget-id="system-health">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i data-feather="heart" class="me-2"></i>System Health
        </h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary widget-toggle" data-widget="system-health" title="Minimize">
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </div>
    <div class="card-body widget-content">
        @if(empty($healthData))
            <div class="text-center py-4">
                <i data-feather="heart" class="text-muted" style="width: 48px; height: 48px;"></i>
                <p class="text-muted mt-3">No health data available</p>
            </div>
        @else
            {{-- Overall Health Score --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card border-{{ $combined['health_status'] === 'excellent' ? 'success' : ($combined['health_status'] === 'good' ? 'info' : ($combined['health_status'] === 'warning' ? 'warning' : 'danger')) }}">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">Overall System Health Score</h6>
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <div class="position-relative" style="width: 150px; height: 150px;">
                                    @php
                                        $circumference = 2 * pi() * 65;
                                        $score = $combined['overall_score'] ?? 0;
                                        $offset = $circumference * (1 - $score / 100);
                                    @endphp
                                    <svg class="position-absolute" width="150" height="150" style="transform: rotate(-90deg);">
                                        <circle cx="75" cy="75" r="65" stroke="#e9ecef" stroke-width="10" fill="none"></circle>
                                        <circle cx="75" cy="75" r="65"
                                                stroke="{{ $combined['health_status'] === 'excellent' ? '#10b981' : ($combined['health_status'] === 'good' ? '#3b82f6' : ($combined['health_status'] === 'warning' ? '#f59e0b' : '#ef4444')) }}"
                                                stroke-width="10"
                                                fill="none"
                                                stroke-dasharray="{{ $circumference }}"
                                                stroke-dashoffset="{{ $offset }}"
                                                stroke-linecap="round"></circle>
                                    </svg>
                                    <div class="position-absolute top-50 start-50 translate-middle text-center">
                                        <h2 class="mb-0">{{ $combined['overall_score'] ?? 0 }}</h2>
                                        <small class="text-muted">/ 100</small>
                                    </div>
                                </div>
                            </div>
                            <h5 class="mb-0">
                                <span class="badge bg-{{ $combined['health_status'] === 'excellent' ? 'success' : ($combined['health_status'] === 'good' ? 'info' : ($combined['health_status'] === 'warning' ? 'warning' : 'danger')) }}">
                                    {{ ucfirst($combined['health_status'] ?? 'unknown') }}
                                </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Health Metrics Comparison --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Coordinator Hierarchy Health</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Health Score</small>
                                <h4 class="mb-0">{{ $coordinatorHierarchy['overall_score'] ?? 0 }}/100</h4>
                                <span class="badge bg-{{ $coordinatorHierarchy['health_status'] === 'excellent' ? 'success' : ($coordinatorHierarchy['health_status'] === 'good' ? 'info' : ($coordinatorHierarchy['health_status'] === 'warning' ? 'warning' : 'danger')) }}">
                                    {{ ucfirst($coordinatorHierarchy['health_status'] ?? 'unknown') }}
                                </span>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted d-block">Approval Rate</small>
                                    <strong>{{ format_indian_percentage($coordinatorHierarchy['approval_rate'] ?? 0, 1) }}</strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted d-block">Processing Time</small>
                                    <strong>{{ $coordinatorHierarchy['avg_processing_time'] ?? 0 }} days</strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted d-block">Completion Rate</small>
                                    <strong>{{ format_indian_percentage($coordinatorHierarchy['completion_rate'] ?? 0, 1) }}</strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted d-block">Activity Rate</small>
                                    <strong>{{ format_indian_percentage($coordinatorHierarchy['activity_rate'] ?? 0, 1) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">Direct Team Health</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Health Score</small>
                                <h4 class="mb-0">{{ $directTeam['overall_score'] ?? 0 }}/100</h4>
                                <span class="badge bg-{{ $directTeam['health_status'] === 'excellent' ? 'success' : ($directTeam['health_status'] === 'good' ? 'info' : ($directTeam['health_status'] === 'warning' ? 'warning' : 'danger')) }}">
                                    {{ ucfirst($directTeam['health_status'] ?? 'unknown') }}
                                </span>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted d-block">Approval Rate</small>
                                    <strong>{{ format_indian_percentage($directTeam['approval_rate'] ?? 0, 1) }}</strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted d-block">Processing Time</small>
                                    <strong>{{ $directTeam['avg_processing_time'] ?? 0 }} days</strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted d-block">Completion Rate</small>
                                    <strong>{{ format_indian_percentage($directTeam['completion_rate'] ?? 0, 1) }}</strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted d-block">Activity Rate</small>
                                    <strong>{{ format_indian_percentage($directTeam['activity_rate'] ?? 0, 1) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if(!empty($combined['alerts']) && count($combined['alerts']) > 0)
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i data-feather="alert-triangle" class="me-2" style="width: 16px; height: 16px;"></i>
                                    System Alerts
                                </h6>
                            </div>
                            <div class="card-body">
                                @foreach($combined['alerts'] as $alert)
                                    <div class="alert alert-{{ $alert['type'] === 'critical' ? 'danger' : ($alert['type'] === 'warning' ? 'warning' : 'info') }} d-flex align-items-center mb-2" role="alert">
                                        <i data-feather="{{ $alert['icon'] ?? 'alert-circle' }}" class="me-2" style="width: 18px; height: 18px;"></i>
                                        <div>{{ $alert['message'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Health Factors Breakdown --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Health Factors Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block mb-1">Approval Rate</small>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                             style="width: {{ min(100, $combined['health_factors']['approval_rate'] ?? 0) }}%"
                                             aria-valuenow="{{ $combined['health_factors']['approval_rate'] ?? 0 }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            {{ format_indian_percentage($combined['health_factors']['approval_rate'] ?? 0, 1) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block mb-1">Processing Time</small>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-info" role="progressbar"
                                             style="width: {{ min(100, $combined['health_factors']['processing_time'] ?? 0) }}%"
                                             aria-valuenow="{{ $combined['health_factors']['processing_time'] ?? 0 }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            {{ format_indian_percentage($combined['health_factors']['processing_time'] ?? 0, 1) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block mb-1">Completion Rate</small>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-primary" role="progressbar"
                                             style="width: {{ min(100, $combined['health_factors']['completion_rate'] ?? 0) }}%"
                                             aria-valuenow="{{ $combined['health_factors']['completion_rate'] ?? 0 }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            {{ format_indian_percentage($combined['health_factors']['completion_rate'] ?? 0, 1) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block mb-1">Budget Utilization</small>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-{{ ($combined['budget_utilization'] ?? 0) > 80 ? 'danger' : (($combined['budget_utilization'] ?? 0) > 60 ? 'warning' : 'success') }}"
                                             role="progressbar"
                                             style="width: {{ min(100, $combined['budget_utilization'] ?? 0) }}%"
                                             aria-valuenow="{{ $combined['budget_utilization'] ?? 0 }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            {{ format_indian_percentage($combined['budget_utilization'] ?? 0, 1) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block mb-1">Activity Rate</small>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-warning" role="progressbar"
                                             style="width: {{ min(100, $combined['health_factors']['activity_rate'] ?? 0) }}%"
                                             aria-valuenow="{{ $combined['health_factors']['activity_rate'] ?? 0 }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            {{ format_indian_percentage($combined['health_factors']['activity_rate'] ?? 0, 1) }}
                                        </div>
                                    </div>
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
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endpush
