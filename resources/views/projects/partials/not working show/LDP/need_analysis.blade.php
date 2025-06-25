{{-- resources/views/projects/partials/Show/LDP/need_analysis.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Need Analysis</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            @if($needAnalysis && $needAnalysis->document_path)
                <div class="info-label"><strong>Need Analysis Document:</strong></div>
                <div class="info-value">
                    <a href="{{ Storage::url($needAnalysis->document_path) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ Storage::url($needAnalysis->document_path) }}" download class="btn btn-sm btn-secondary">Download</a>
                </div>
            @else
                <div class="info-label"><strong>Status:</strong></div>
                <div class="info-value text-muted">No document uploaded yet.</div>
            @endif
        </div>
    </div>
</div>
