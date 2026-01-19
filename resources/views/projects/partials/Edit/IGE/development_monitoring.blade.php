{{-- resources/views/projects/partials/Edit/IGE/development_monitoring.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Development Monitoring</h4>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="proposed_activities">Proposed Activities for Overall Development</label>
            <textarea name="proposed_activities" class="form-control sustainability-textarea" rows="4">{{ old('proposed_activities', $developmentMonitoring->proposed_activities ?? '') }}</textarea>
        </div>
        <br>
        <div class="form-group">
            <label for="monitoring_methods">Methods of Monitoring the Beneficiaries' Growth</label>
            <textarea name="monitoring_methods" class="form-control sustainability-textarea" rows="4">{{ old('monitoring_methods', $developmentMonitoring->monitoring_methods ?? '') }}</textarea>
        </div>
        <br>
        <div class="form-group">
            <label for="evaluation_process">Process of Evaluation and Responsibility</label>
            <textarea name="evaluation_process" class="form-control sustainability-textarea" rows="4">{{ old('evaluation_process', $developmentMonitoring->evaluation_process ?? '') }}</textarea>
        </div>
        <br>
        <div class="form-group">
            <label for="conclusion">Conclusion</label>
            <textarea name="conclusion" class="form-control sustainability-textarea" rows="4">{{ old('conclusion', $developmentMonitoring->conclusion ?? '') }}</textarea>
        </div>
    </div>
</div>
