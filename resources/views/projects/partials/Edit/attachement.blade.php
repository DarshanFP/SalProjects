{{-- resources/views/projects/partials/Edit/attachement.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>3. Attachment</h4>
    </div>
    <div class="card-body">
        @if($project->attachments->isNotEmpty())
            <div class="mb-3">
                <label class="form-label">Current Attachment</label>
                <input type="text" class="form-control" value="{{ $project->attachments[0]->file_name }}" readonly>
                <textarea class="form-control" rows="3" readonly>{{ $project->attachments[0]->description }}</textarea>
                <a href="{{ route('download.attachment', $project->attachments[0]->id) }}" class="mt-2 btn btn-secondary">Download Existing Attachment</a>
            </div>
        @endif

        <div class="mb-3">
            <label for="file" class="form-label">Replace Attachment (PDF, max 10 MB)</label>
            <input type="file" name="file" id="file" class="form-control" accept=".pdf" onchange="checkFileSize(this)">
            <label for="file_name" class="form-label">File Name</label>
            <input type="text" name="file_name" class="form-control" placeholder="Name of File Attached" value="{{ $project->attachments->isNotEmpty() ? $project->attachments[0]->file_name : '' }}">
            <label for="attachment_description" class="form-label">Brief Description</label>
            <textarea name="attachment_description" class="form-control" rows="3" placeholder="Describe the file">{{ $project->attachments->isNotEmpty() ? $project->attachments[0]->description : '' }}</textarea>
        </div>
        <p id="file-size-warning" style="color: red; display: none;">File size must not exceed 10 MB!</p>
    </div>
</div>

<script>
function checkFileSize(input) {
    const file = input.files[0];
    if (file && file.size > 10485760) { // 10 MB in bytes
        document.getElementById('file-size-warning').style.display = 'block';
        input.value = ''; // Reset the file input
    } else {
        document.getElementById('file-size-warning').style.display = 'none';
    }
}
</script>
