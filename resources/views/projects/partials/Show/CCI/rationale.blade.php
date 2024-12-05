{{-- resources/views/projects/partials/Show/CCI/rationale.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Rationale</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <h5>Description</h5>
            <p>
                {{ $rationale->description ?? 'No rationale provided yet.' }}
            </p>
        </div>
    </div>
</div>

<!-- Styles for textarea and text display -->
{{-- <style>
    .card-body p {
        background-color: #f9f9f9;
        padding: 1rem;
        border-radius: 4px;
        border: 1px solid #ddd;
        white-space: pre-wrap; /* Preserve line breaks */
    }

    h5 {
        color: #202ba3;
        font-weight: bold;
    }
</style> --}}
