{{-- resources/views/projects/partials/Show/IGE/development_monitoring.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Development Monitoring</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-label"><strong>Proposed Activities for Overall Development:</strong></div>
            <div class="info-value">{{ $developmentMonitoring?->proposed_activities ?? 'No data provided.' }}</div>

            <div class="info-label"><strong>Methods of Monitoring the Beneficiaries’ Growth:</strong></div>
            <div class="info-value">{{ $developmentMonitoring?->monitoring_methods ?? 'No data provided.' }}</div>

            <div class="info-label"><strong>Process of Evaluation and Responsibility:</strong></div>
            <div class="info-value">{{ $developmentMonitoring?->evaluation_process ?? 'No data provided.' }}</div>

            <div class="info-label"><strong>Conclusion:</strong></div>
            <div class="info-value">{{ $developmentMonitoring?->conclusion ?? 'No data provided.' }}</div>
        </div>
    </div>
</div>
