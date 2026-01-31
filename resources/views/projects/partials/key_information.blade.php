<!-- resources/views/projects/partials/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information</h4>
    </div>
    <div class="card-body">
        <h5 class="mb-3">Background of the project</h5>

        <!-- Prevailing social situation in the project area and its adverse effect on life -->
        <div class="mb-3">
            <label for="initial_information" class="form-label">Prevailing social situation in the project area and its adverse effect on life</label>
            <textarea name="initial_information" id="initial_information" class="form-control sustainability-textarea" rows="3">{{ old('initial_information') }}</textarea>
            @error('initial_information')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Detailed information on target beneficiary of the project -->
        <div class="mb-3">
            <label for="target_beneficiaries" class="form-label">Detailed information on target beneficiary of the project</label>
            <textarea name="target_beneficiaries" id="target_beneficiaries" class="form-control sustainability-textarea" rows="3">{{ old('target_beneficiaries') }}</textarea>
            @error('target_beneficiaries')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Educational & cultural situation in the project area -->
        <div class="mb-3">
            <label for="general_situation" class="form-label">Educational & cultural situation in the project area</label>
            <textarea name="general_situation" id="general_situation" class="form-control sustainability-textarea" rows="3">{{ old('general_situation') }}</textarea>
            @error('general_situation')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Need of the Project -->
        <div class="mb-3">
            <label for="need_of_project" class="form-label">Need of the Project</label>
            <textarea name="need_of_project" id="need_of_project" class="form-control sustainability-textarea" rows="3">{{ old('need_of_project') }}</textarea>
            @error('need_of_project')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Prevailing economic situation in the project area -->
        <div class="mb-3">
            <label for="economic_situation" class="form-label">Prevailing economic situation in the project area</label>
            <textarea name="economic_situation" id="economic_situation" class="form-control sustainability-textarea" rows="3">{{ old('economic_situation') }}</textarea>
            @error('economic_situation')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Goal of the Project (Existing, Last) -->
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" id="goal" class="form-control sustainability-textarea" rows="3">{{ old('goal') }}</textarea>
            @error('goal')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Problem Tree Image -->
        <div class="mb-3 problem-tree-upload-wrapper" data-has-existing="0">
            <label for="problem_tree_image" class="form-label">Problem Tree (image)</label>
            <small class="d-block text-muted mb-1">Cause and effect of the problem</small>
            <div id="problem_tree_new_preview" class="problem-tree-preview mb-2" style="display:none;">
                <span class="d-block small text-muted mb-1">Preview (image will be resized when saved):</span>
                <img id="problem_tree_new_preview_img" src="" alt="Preview" class="img-thumbnail" style="max-height:200px; max-width:100%;">
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
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                var input = document.getElementById('problem_tree_image');
                var preview = document.getElementById('problem_tree_new_preview');
                var previewImg = document.getElementById('problem_tree_new_preview_img');
                if (!input || !preview || !previewImg) return;
                var currentUrl = null;
                input.addEventListener('change', function() {
                    if (currentUrl) { URL.revokeObjectURL(currentUrl); currentUrl = null; }
                    if (this.files.length && this.files[0].type.match(/^image\//)) {
                        currentUrl = URL.createObjectURL(this.files[0]);
                        previewImg.src = currentUrl;
                        preview.style.display = 'block';
                    } else {
                        previewImg.src = '';
                        preview.style.display = 'none';
                    }
                });
            });
        })();
        </script>
        <script src="{{ asset('js/key-information-validation.js') }}"></script>
    </div>
</div>
