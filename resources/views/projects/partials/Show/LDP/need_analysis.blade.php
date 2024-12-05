<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Need Analysis</h4>
        <p>(Upload updated information of Need Analysis or view the existing document)</p>
    </div>
    <div class="card-body">
        @if($needAnalysis && $needAnalysis->document_path)
            <div class="mb-3">
                <p>Current Document:
                    <a href="{{ Storage::url($needAnalysis->document_path) }}" target="_blank">View Document</a>
                </p>
            </div>
        @else
            <div class="mb-3">
                <p>No document uploaded yet.</p>
            </div>
        @endif

        <div class="mb-3">
            <label for="need_analysis_file" class="form-label">Upload New Need Analysis Document:</label>
            <input type="file" name="need_analysis_file" class="form-control" accept=".pdf,.doc,.docx">
        </div>
    </div>
</div>
