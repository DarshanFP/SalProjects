
{{-- resources/views/projects/partials/show/key_information.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-label"><strong>Goal of the Project:</strong></div>
            <div class="info-value">{{ $project->goal }}</div>
        </div>
    </div>
</div>
