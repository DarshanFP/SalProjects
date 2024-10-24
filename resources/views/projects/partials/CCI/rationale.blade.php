{{-- <div class="form-group">
    <label for="rationale" class="form-label"><strong>III. Rationale</strong></label>


                    <span style="color: #202ba3;">
                        Explain in brief, the services rendered and how this will contribute in achieving the goal.

                    <textarea name="description" id="description" rows="4" class="form-control"></textarea>

</div> --}}

<!-- resources/views/projects/partials/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4> Rationale</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="description" class="form-label">Explain in brief, the services rendered and how this will contribute in achieving the goal.</label>
            <textarea name="description" class="form-control select-input" rows="3" required  style="background-color: #202ba3;">{{ old('goal') }}</textarea>
        </div>
    </div>
</div>

