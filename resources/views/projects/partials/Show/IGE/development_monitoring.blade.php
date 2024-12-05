<div class="mb-3 card">
    <div class="card-header">
        <h4>Development Monitoring</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <h5>Proposed Activities for Overall Development</h5>
            <p>{{ $developmentMonitoring?->proposed_activities ?? 'No data provided.' }}</p>
        </div>
        <div class="mb-3">
            <h5>Methods of Monitoring the Beneficiaries’ Growth</h5>
            <p>{{ $developmentMonitoring?->monitoring_methods ?? 'No data provided.' }}</p>
        </div>
        <div class="mb-3">
            <h5>Process of Evaluation and Responsibility</h5>
            <p>{{ $developmentMonitoring?->evaluation_process ?? 'No data provided.' }}</p>
        </div>
        <div class="mb-3">
            <h5>Conclusion</h5>
            <p>{{ $developmentMonitoring?->conclusion ?? 'No data provided.' }}</p>
        </div>
    </div>
</div>
