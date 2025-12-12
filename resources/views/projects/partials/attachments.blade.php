<!-- resources/views/projects/partials/attachments.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachment</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="file" class="form-label">Attachment File</label>
            <label class="form-label"><i>(PDF, DOC, DOCX files are allowed, maximum 2 MB)</i></label>

            <input type="file" name="file" id="file" class="mb-2 form-control @error('file') is-invalid @enderror"
                   accept=".pdf, .doc, .docx" onchange="validateFile(this)" style="background-color: #202ba3;">

            <div id="file-preview" class="mt-2" style="display: none;">
                <div class="alert alert-info">
                    <i class="fas fa-file" id="file-icon"></i> <span id="file-name"></span>
                    <br><small>Size: <span id="file-size"></span></small>
                </div>
            </div>

            <div id="file-size-warning" class="alert alert-danger mt-2" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i> File size must not exceed 2 MB!
            </div>

            <div id="file-type-warning" class="alert alert-danger mt-2" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i> Only PDF, DOC, and DOCX files are allowed!
            </div>

            @error('file')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="file_name" class="form-label">File Name</label>
            <input type="text" name="file_name" id="file_name" class="mb-2 form-control @error('file_name') is-invalid @enderror"
                   placeholder="Enter a descriptive name for the file" style="background-color: #202ba3;">

            @error('file_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="attachment_description" class="form-label">Brief Description</label>
            <textarea name="attachment_description" id="attachment_description" class="form-control @error('attachment_description') is-invalid @enderror"
                      rows="3" placeholder="Describe the content and purpose of this file" style="background-color: #202ba3;"></textarea>

            @error('attachment_description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<script>
function validateFile(input) {
    const file = input.files[0];
    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const maxSize = 2097152; // 2MB

    // Reset warnings
    document.getElementById('file-size-warning').style.display = 'none';
    document.getElementById('file-type-warning').style.display = 'none';
    document.getElementById('file-preview').style.display = 'none';

    if (file) {
        // Check file type
        if (!validTypes.includes(file.type)) {
            document.getElementById('file-type-warning').style.display = 'block';
            input.value = '';
            return;
        }

        // Check file size
        if (file.size > maxSize) {
            document.getElementById('file-size-warning').style.display = 'block';
            input.value = '';
            return;
        }

        // Show file preview with appropriate icon
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-size').textContent = formatFileSize(file.size);

        // Set appropriate icon based on file type
        const fileIcon = document.getElementById('file-icon');
        if (file.type === 'application/pdf') {
            fileIcon.className = 'fas fa-file-pdf text-danger';
        } else if (file.type === 'application/msword') {
            fileIcon.className = 'fas fa-file-word text-primary';
        } else if (file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            fileIcon.className = 'fas fa-file-word text-primary';
        } else {
            fileIcon.className = 'fas fa-file text-secondary';
        }

        document.getElementById('file-preview').style.display = 'block';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
