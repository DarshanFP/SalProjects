{{-- resources/views/projects/partials/LDP/intervention_logic.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Intervention Logic</h4>
        <p>Describe how the interventions of the project can alleviate the existing problems.</p>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <textarea name="intervention_description" class="form-control sustainability-textarea" rows="5" placeholder="Describe the interventions...">{{ isset($interventionLogic) ? $interventionLogic->intervention_description : '' }}</textarea>
        </div>
    </div>
</div>
