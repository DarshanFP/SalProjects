{{-- resources/views/projects/partials/CCI/present_situation.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Present situation of the inmates</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="internal_challenges" class="form-label">(1) Internal challenges faced from inmates</label>
            <textarea name="internal_challenges" class="form-control" rows="3" style="background-color: #202ba3;" placeholder="Describe internal challenges"></textarea>
        </div>

        <div class="mb-3">
            <label for="external_challenges" class="form-label">(2) External challenges / Present difficulties</label>
            <textarea name="external_challenges" class="form-control" rows="3" style="background-color: #202ba3;" placeholder="Describe external challenges"></textarea>
        </div>
    </div>

    <div class="card-header">
        <h4>Area of focus for the current year</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="area_of_focus" class="form-label">
                What are the main areas you want to focus on for the growth/development of the institution and children? Explain why you want to focus on these areas and what you intend to achieve?
            </label>
            <textarea name="area_of_focus" class="form-control" rows="4" style="background-color: #202ba3;" placeholder="Specify areas of focus"></textarea>
        </div>
    </div>
</div>

<!-- Styles for table and input fields -->
<style>
    .form-control {
        width: 100%;
        box-sizing: border-box;
    }

    .form-label {
        font-weight: bold;
    }

    textarea {
        resize: none;
    }
</style>
