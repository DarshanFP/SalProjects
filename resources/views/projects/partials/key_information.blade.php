<!-- resources/views/projects/partials/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information</h4>
    </div>
    <div class="card-body">
        <!-- Initial Information -->
        <div class="mb-3">
            <label for="initial_information" class="form-label">Initial Information</label>
            <textarea name="initial_information" id="initial_information" class="form-control sustainability-textarea" rows="3">{{ old('initial_information') }}</textarea>
            @error('initial_information')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Target Beneficiaries -->
        <div class="mb-3">
            <label for="target_beneficiaries" class="form-label">Target Beneficiaries</label>
            <textarea name="target_beneficiaries" id="target_beneficiaries" class="form-control sustainability-textarea" rows="3">{{ old('target_beneficiaries') }}</textarea>
            @error('target_beneficiaries')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- General Situation -->
        <div class="mb-3">
            <label for="general_situation" class="form-label">General Situation</label>
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

        <!-- Goal of the Project (Existing, Last) -->
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" id="goal" class="form-control sustainability-textarea" rows="3">{{ old('goal') }}</textarea>
            @error('goal')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
