<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Rationale</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="description" class="form-label">Explain in brief, the services rendered and how this will contribute in achieving the goal.</label>
            <textarea name="description" class="form-control select-input" rows="3" style="background-color: #202ba3;">{{ $rationale->description ?? '' }}</textarea>
        </div>
    </div>
</div>

<!-- Styles for textarea -->
<style>
    .form-control {
        width: 100%;
        box-sizing: border-box;
    }
</style>
