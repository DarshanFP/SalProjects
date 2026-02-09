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
            <div class="word-guidance d-flex justify-content-between align-items-center" data-target="initial_information">
                <small style="color:#6bbb59ff;">Minimum 100 words required.</small>
                <span class="word-counter" id="initial_information_counter" style="color:#6bbb59ff;">0 / 100 words</span>
            </div>
        </div>

        <!-- Detailed information on target beneficiary of the project -->
        <div class="mb-3">
            <label for="target_beneficiaries" class="form-label">Detailed information on target beneficiary of the project</label>
            <textarea name="target_beneficiaries" id="target_beneficiaries" class="form-control sustainability-textarea" rows="3">{{ old('target_beneficiaries') }}</textarea>
            @error('target_beneficiaries')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <div class="word-guidance d-flex justify-content-between align-items-center" data-target="target_beneficiaries">
                <small style="color:#6bbb59ff;">Minimum 100 words required.</small>
                <span class="word-counter" id="target_beneficiaries_counter" style="color:#6bbb59ff;">0 / 100 words</span>
            </div>
        </div>

        <!-- Educational & cultural situation in the project area -->
        <div class="mb-3">
            <label for="general_situation" class="form-label">Educational & cultural situation in the project area</label>
            <textarea name="general_situation" id="general_situation" class="form-control sustainability-textarea" rows="3">{{ old('general_situation') }}</textarea>
            @error('general_situation')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <div class="word-guidance d-flex justify-content-between align-items-center" data-target="general_situation">
                <small style="color:#6bbb59ff;">Minimum 100 words required.</small>
                <span class="word-counter" id="general_situation_counter" style="color:#6bbb59ff;">0 / 100 words</span>
            </div>
        </div>

        <!-- Need of the Project -->
        <div class="mb-3">
            <label for="need_of_project" class="form-label">Need of the Project</label>
            <textarea name="need_of_project" id="need_of_project" class="form-control sustainability-textarea" rows="3">{{ old('need_of_project') }}</textarea>
            @error('need_of_project')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <div class="word-guidance d-flex justify-content-between align-items-center" data-target="need_of_project">
                <small style="color:#6bbb59ff;">Minimum 100 words required.</small>
                <span class="word-counter" id="need_of_project_counter" style="color:#6bbb59ff;">0 / 100 words</span>
            </div>
        </div>

        <!-- Prevailing economic situation in the project area -->
        <div class="mb-3">
            <label for="economic_situation" class="form-label">Prevailing economic situation in the project area</label>
            <textarea name="economic_situation" id="economic_situation" class="form-control sustainability-textarea" rows="3">{{ old('economic_situation') }}</textarea>
            @error('economic_situation')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <div class="word-guidance d-flex justify-content-between align-items-center" data-target="economic_situation">
                <small style="color:#6bbb59ff;">Minimum 100 words required.</small>
                <span class="word-counter" id="economic_situation_counter" style="color:#6bbb59ff;">0 / 100 words</span>
            </div>
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
        <script>
        (function() {
            function setupWordCounter(textareaId, counterId) {
                var textarea = document.getElementById(textareaId);
                var counter = document.getElementById(counterId);
                if (!textarea || !counter) return;
                function updateCount() {
                    var text = textarea.value.trim();
                    var words = text.length > 0 ? text.split(/\s+/).filter(Boolean).length : 0;
                    counter.textContent = words + " / 100 words";
                }
                textarea.addEventListener('input', updateCount);
                updateCount();
            }
            document.addEventListener('DOMContentLoaded', function() {
                setupWordCounter('initial_information', 'initial_information_counter');
                setupWordCounter('target_beneficiaries', 'target_beneficiaries_counter');
                setupWordCounter('general_situation', 'general_situation_counter');
                setupWordCounter('need_of_project', 'need_of_project_counter');
                setupWordCounter('economic_situation', 'economic_situation_counter');
            });
        })();
        </script>
    </div>
</div>
