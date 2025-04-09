<!-- resources/views/projects/partials/Edit/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Key Information</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project:</label>
    <textarea name="goal" id="goal" class="form-control" rows="3"  style="background-color: #202ba3;">{{ $project->goal }}</textarea>

        </div>
    </div>
</div>

<!-- resources/views/projects/partials/key_information.blade.php -->
{{-- <div class="mb-3 card">
    <div class="card-header">
        <h4> Key THIS IS EXTRA Information</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" class="form-control select-input" rows="3" required  style="background-color: #202ba3;">{{ $project->goal }}</textarea>
        </div>
    </div>
</div> --}}
