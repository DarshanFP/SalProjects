{{-- resources/views/projects/partials/Edit/LDP/intervention_logic.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Intervention Logic</h4>
        <p>Update the description of how the interventions of the project can alleviate the existing problems.</p>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <textarea name="intervention_description" class="form-control" rows="5" style="background-color: #202ba3;" placeholder="Update the interventions...">{{ isset($interventionLogic) ? $interventionLogic->intervention_description : '' }}</textarea>
        </div>
    </div>
</div>
