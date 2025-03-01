<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information - Next Phase</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" id="goal" class="form-control select-input" rows="3" required style="background-color: #202ba3;">
                {{ old('goal', $predecessorGoal ?? '') }}
            </textarea>
        </div>
    </div>
</div>
