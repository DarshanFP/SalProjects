{{-- resources/views/projects/partials/LDP/need_analysis.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Need Analysis</h4>
        <p>(Upload detailed information of Need Analysis pertaining specifically to this project)</p>
    </div>

    <div class="card-body">
        <div class="mb-3">
            <label for="need_analysis_file" class="form-label">Upload Need Analysis Document:</label>
            <input type="file" name="need_analysis_file" class="form-control" accept=".pdf,.doc,.docx">
            @if($document_path)
                <p>Current file: <a href="{{ asset('storage/' . $document_path) }}" target="_blank">View Document</a></p>
            @endif
        </div>
    </div>
</div>
