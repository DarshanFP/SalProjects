<!-- resources/views/projects/partials/attachments.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachment</h4>
    </div>
    <div class="card-body">
        <form id="attachmentForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">Attachment File <span class="text-danger">*</span></label>
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
                <label for="file_name" class="form-label">File Name <span class="text-danger">*</span></label>
                <input type="text" name="file_name" id="file_name" class="mb-2 form-control @error('file_name') is-invalid @enderror"
                       placeholder="Enter a descriptive name for the file" style="background-color: #202ba3;" required>

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

            <div class="mb-3">
                <button type="button" id="uploadBtn" class="btn btn-primary" onclick="uploadAttachment()" disabled>
                    <i class="fas fa-upload"></i> Upload Attachment
                </button>
                <div id="upload-progress" class="progress mt-2" style="display: none;">
                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let selectedFile = null;

function validateFile(input) {
    const file = input.files[0];
    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const maxSize = 2097152; // 2MB

    // Reset warnings
    document.getElementById('file-size-warning').style.display = 'none';
    document.getElementById('file-type-warning').style.display = 'none';
    document.getElementById('file-preview').style.display = 'none';
    document.getElementById('uploadBtn').disabled = true;

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
        selectedFile = file;
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

        // Enable upload button if file name is provided
        const fileName = document.getElementById('file_name').value.trim();
        document.getElementById('uploadBtn').disabled = !fileName;
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Enable upload button when file name is entered
document.getElementById('file_name').addEventListener('input', function() {
    const fileName = this.value.trim();
    const hasFile = selectedFile !== null;
    document.getElementById('uploadBtn').disabled = !fileName || !hasFile;
});

function uploadAttachment() {
    const formData = new FormData();
    const file = document.getElementById('file').files[0];
    const fileName = document.getElementById('file_name').value.trim();
    const description = document.getElementById('attachment_description').value.trim();

    if (!file || !fileName) {
        showAlert('warning', 'Please select a file and enter a file name.');
        return;
    }

    formData.append('file', file);
    formData.append('file_name', fileName);
    formData.append('attachment_description', description);
    formData.append('_token', document.querySelector('input[name="_token"]').value);

    // Show progress bar
    const progressBar = document.querySelector('.progress-bar');
    const progressDiv = document.getElementById('upload-progress');
    const uploadBtn = document.getElementById('uploadBtn');

    progressDiv.style.display = 'block';
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    // Simulate progress
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 90) progress = 90;
        progressBar.style.width = progress + '%';
    }, 200);

    // Make the upload request
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            // Handle redirect response
            window.location.href = response.url;
            return;
        }
        return response.text();
    })
    .then(data => {
        clearInterval(progressInterval);
        progressBar.style.width = '100%';

        // Check if there are validation errors in the response
        if (data && data.includes('validation-errors')) {
            showAlert('danger', 'Please check the form for errors.');
            // Reload page to show validation errors
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('success', 'File uploaded successfully!');

            // Reset form
            document.getElementById('attachmentForm').reset();
            document.getElementById('file-preview').style.display = 'none';
            document.getElementById('uploadBtn').disabled = true;
            selectedFile = null;

            // Reload page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }
    })
    .catch(error => {
        clearInterval(progressInterval);
        console.error('Upload error:', error);
        showAlert('danger', 'An error occurred during upload. Please try again.');
    })
    .finally(() => {
        progressDiv.style.display = 'none';
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Attachment';
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const cardBody = document.querySelector('.card-body');
    cardBody.insertBefore(alertDiv, cardBody.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
