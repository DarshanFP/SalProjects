<!-- resources/views/projects/partials/Edit/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Key Information</h4>
    </div>
    <div class="card-body">
        <!-- Initial Information -->
        <div class="mb-3">
            <label for="initial_information" class="form-label">Initial Information</label>
            <textarea name="initial_information" id="initial_information" class="form-control sustainability-textarea" rows="3">{{ old('initial_information', $project->initial_information) }}</textarea>
            @error('initial_information')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Target Beneficiaries -->
        <div class="mb-3">
            <label for="target_beneficiaries" class="form-label">Target Beneficiaries</label>
            <textarea name="target_beneficiaries" id="target_beneficiaries" class="form-control sustainability-textarea" rows="3">{{ old('target_beneficiaries', $project->target_beneficiaries) }}</textarea>
            @error('target_beneficiaries')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- General Situation -->
        <div class="mb-3">
            <label for="general_situation" class="form-label">General Situation</label>
            <textarea name="general_situation" id="general_situation" class="form-control sustainability-textarea" rows="3">{{ old('general_situation', $project->general_situation) }}</textarea>
            @error('general_situation')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Need of the Project -->
        <div class="mb-3">
            <label for="need_of_project" class="form-label">Need of the Project</label>
            <textarea name="need_of_project" id="need_of_project" class="form-control sustainability-textarea" rows="3">{{ old('need_of_project', $project->need_of_project) }}</textarea>
            @error('need_of_project')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Goal of the Project (Existing, Last) -->
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" id="goal" class="form-control sustainability-textarea" rows="3">{{ old('goal', $project->goal) }}</textarea>
            @error('goal')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Problem Tree Image -->
        <div class="mb-3 problem-tree-upload-wrapper" data-has-existing="{{ $project->problem_tree_file_path ? '1' : '0' }}">
            <label for="problem_tree_image" class="form-label">Problem Tree (image)</label>
            @if($project->problem_tree_file_path)
                <div class="mb-2 problem-tree-current-preview">
                    <span class="d-block small text-muted mb-1">Current image (preview):</span>
                    <img src="{{ $project->problem_tree_image_url }}" alt="Problem Tree" class="img-thumbnail" style="max-height:200px; max-width:100%;">
                    <small class="d-block text-muted mt-1">Choosing a new file will replace it (after confirmation).</small>
                </div>
            @endif
            <div id="problem_tree_new_preview" class="problem-tree-preview mb-2" style="display:none;">
                <span class="d-block small text-muted mb-1">New image preview (will be resized when saved):</span>
                <img id="problem_tree_new_preview_img" src="" alt="New preview" class="img-thumbnail" style="max-height:200px; max-width:100%;">
            </div>
            <input type="file" name="problem_tree_image" id="problem_tree_image"
                   class="form-control"
                   accept="image/jpeg,image/jpg,image/png">
            <small class="form-text text-muted">One image per project. Allowed: JPG, PNG. Max 7 MB. Image will be resized to reduce file size.</small>
            @error('problem_tree_image')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <script>
        (function () {
            document.addEventListener('DOMContentLoaded', function () {
                var input = document.getElementById('problem_tree_image');
                var newPreview = document.getElementById('problem_tree_new_preview');
                var newPreviewImg = document.getElementById('problem_tree_new_preview_img');
                if (!input) return;
                var wrapper = input.closest('.problem-tree-upload-wrapper');
                var hasExisting = wrapper && wrapper.getAttribute('data-has-existing') === '1';
                var currentUrl = null;

                function hideNewPreview() {
                    if (currentUrl) { URL.revokeObjectURL(currentUrl); currentUrl = null; }
                    if (newPreviewImg) newPreviewImg.src = '';
                    if (newPreview) newPreview.style.display = 'none';
                }

                input.addEventListener('change', function () {
                    if (this.files.length === 0) {
                        hideNewPreview();
                        return;
                    }
                    if (hasExisting) {
                        var msg = 'A Problem Tree image already exists for this project. Uploading a new file will permanently replace it. Do you want to continue?';
                        if (!confirm(msg)) {
                            this.value = '';
                            hideNewPreview();
                            return;
                        }
                    }
                    if (this.files[0].type.match(/^image\//)) {
                        if (currentUrl) URL.revokeObjectURL(currentUrl);
                        currentUrl = URL.createObjectURL(this.files[0]);
                        if (newPreviewImg) newPreviewImg.src = currentUrl;
                        if (newPreview) newPreview.style.display = 'block';
                    } else {
                        hideNewPreview();
                    }
                });
            });
        })();
        </script>
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
            <textarea name="goal" class="form-control select-input" rows="3" required >{{ $project->goal }}</textarea>
        </div>
    </div>
</div> --}}
