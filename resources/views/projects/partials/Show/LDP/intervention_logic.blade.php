{{-- resources/views/projects/partials/Show/LDP/intervention_logic.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Intervention Logic</h4>
        <p><em>Description of how the project's interventions alleviate the existing problems.</em></p>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-label"><strong>Description:</strong></div>
            <div class="info-value">
                {{ $interventionLogic?->intervention_description ?? 'No intervention logic provided.' }}
            </div>
        </div>
    </div>
</div>
