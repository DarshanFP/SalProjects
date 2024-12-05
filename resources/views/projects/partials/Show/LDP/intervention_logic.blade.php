{{-- resources/views/projects/partials/Show/LDP/intervention_logic.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Intervention Logic</h4>
        <p>Description of how the project's interventions alleviate the existing problems.</p>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <h5>Description</h5>
            <p>{{ $interventionLogic?->intervention_description ?? 'No intervention logic provided.' }}</p>
        </div>
    </div>
</div>
