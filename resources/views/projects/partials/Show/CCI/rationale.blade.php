{{-- resources/views/projects/partials/Show/CCI/rationale.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Rationale</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-label"><strong>Description:</strong></div>
            <div class="info-value">{{ $rationale->description ?? 'No rationale provided yet.' }}</div>
        </div>
    </div>
</div>
