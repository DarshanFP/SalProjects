<div class="mb-3 card">
    <div class="card-header">
        <h4>Key Information - Next Phase</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="goal" class="form-label">Goal of the Project</label>
            <textarea name="goal" id="goal" class="form-control select-input sustainability-textarea" rows="3" required>
                {{ old('goal', $predecessorGoal ?? '') }}
            </textarea>
        </div>
    </div>
</div>

<style>
.sustainability-textarea {
    resize: vertical;
    min-height: 80px;
    height: auto;
    overflow-y: hidden;
    line-height: 1.5;
    padding: 8px 12px;
}

.sustainability-textarea:focus {
    overflow-y: auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    const textarea = document.querySelector('textarea[name="goal"]');
    if (textarea) {
        autoResizeTextarea(textarea);
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    }
});
</script>
