<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Risk Analysis</h4>
    </div>
    <div class="card-body">

        <!-- Identified Risks -->
        <div class="mb-3">
            <label for="identified_risks" class="form-label">Identify risks involved in this small business/enterprise:</label>
            <textarea name="identified_risks" class="form-control sustainability-textarea" rows="3" placeholder="Describe the risks"></textarea>
        </div>

        <!-- Mitigation Measures -->
        <div class="mb-3">
            <label for="mitigation_measures" class="form-label">What are the measures proposed to face the above challenges to limit the risks?</label>
            <textarea name="mitigation_measures" class="form-control sustainability-textarea" rows="3" placeholder="Describe the mitigation measures"></textarea>
        </div>

        <!-- Business Sustainability -->
        <div class="mb-3">
            <label for="business_sustainability" class="form-label">Explain the sustainability of the business/enterprise:</label>
            <textarea name="business_sustainability" class="form-control sustainability-textarea" rows="3" placeholder="Explain sustainability"></textarea>
        </div>

        <!-- Expected Profits and Outcomes -->
        <div class="mb-3">
            <label for="expected_profits" class="form-label">What are the other expected profits and outcomes foreseen by this initiative?</label>
            <textarea name="expected_profits" class="form-control sustainability-textarea" rows="3" placeholder="Describe expected profits and outcomes"></textarea>
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
    const textareas = document.querySelectorAll('.sustainability-textarea');
    textareas.forEach(textarea => {
        autoResizeTextarea(textarea);
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
});
</script>
