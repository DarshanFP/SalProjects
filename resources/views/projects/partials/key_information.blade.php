<!-- resources/views/projects/partials/key_information.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" id="goal" class="form-control select-input" rows="3" required style="background-color: #202ba3;">{{ old('goal') }}</textarea>
            @error('goal')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
