{{-- resources/views/projects/partials/Edit/attachement.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>3. Attachment</h4>
    </div>
    <div class="card-body">
        @if($project->attachments->isNotEmpty())
            <div class="mb-4">
                <label class="form-label">Current Attachment</label>
                <div class="card border">
                    <div class="card-body">
                        @php
                            $attachment = $project->attachments[0];
                            $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path);
                            $fileSize = $fileExists ? \Illuminate\Support\Facades\Storage::disk('public')->size($attachment->file_path) : 0;
                            $fileExtension = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                        @endphp

                        @if($fileExists)
                            <div class="mb-2">
                                @if($fileExtension === 'pdf')
                                    <i class="fas fa-file-pdf text-danger"></i>
                                @elseif(in_array($fileExtension, ['doc', 'docx']))
                                    <i class="fas fa-file-word text-primary"></i>
                                @else
                                    <i class="fas fa-file text-secondary"></i>
                                @endif
                                <strong>{{ e($attachment->file_name) }}</strong>
                            </div>
                            <div class="text-muted small mb-2">
                                Size: {{ number_format($fileSize / 1024, 2) }} KB
                            </div>
                            @if($attachment->description)
                                <div class="text-muted small mb-2">
                                    {{ e($attachment->description) }}
                                </div>
                            @endif
                            <div class="btn-group btn-group-sm">
                                <a href="{{ asset('storage/' . $attachment->file_path) }}"
                                   target="_blank"
                                   class="btn btn-outline-primary"
                                   title="View file">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="{{ route('download.attachment', $attachment->id) }}"
                                   class="btn btn-outline-success"
                                   title="Download file">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        @else
                            <div class="alert alert-warning mb-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>{{ e($attachment->file_name) }}</strong> (File not found)
                            </div>
                            <div class="text-muted small mb-2">
                                <strong>Path:</strong> {{ e($attachment->file_path) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle"></i> No attachment found for this project.
            </div>
        @endif

        <div class="mb-3">
            <h5>{{ $project->attachments->isNotEmpty() ? 'Replace Attachment' : 'Add Attachment' }}</h5>
            <form id="attachmentForm" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="file" class="form-label">Attachment File <span class="text-danger">*</span></label>
                    <label class="form-label"><i>(PDF, DOC, DOCX files are allowed, maximum 2 MB)</i></label>

                    <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror"
                           accept=".pdf, .doc, .docx" onchange="validateFile(this)">

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
                    <input type="text" name="file_name" id="file_name" class="form-control @error('file_name') is-invalid @enderror"
                           placeholder="Enter a descriptive name for the file"
                           value="{{ $project->attachments->isNotEmpty() ? e($project->attachments[0]->file_name) : '' }}" required>

                    @error('file_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="attachment_description" class="form-label">Brief Description</label>
                    <textarea name="attachment_description" id="attachment_description" class="form-control @error('attachment_description') is-invalid @enderror"
                              rows="3" placeholder="Describe the content and purpose of this file">{{ $project->attachments->isNotEmpty() ? e($project->attachments[0]->description) : '' }}</textarea>

                    @error('attachment_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <button type="button" id="uploadBtn" class="btn btn-primary" onclick="uploadAttachment()" disabled>
                        <i class="fas fa-{{ $project->attachments->isNotEmpty() ? 'sync' : 'upload' }}"></i>
                        {{ $project->attachments->isNotEmpty() ? 'Replace Attachment' : 'Upload Attachment' }}
                    </button>
                    <div id="upload-progress" class="progress mt-2" style="display: none;">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </form>
        </div>
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

    // Show confirmation for replacement
    const hasExistingAttachment = {{ $project->attachments->isNotEmpty() ? 'true' : 'false' }};
    if (hasExistingAttachment) {
        if (!confirm('Are you sure you want to replace the existing attachment? This action cannot be undone.')) {
            return;
        }
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
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

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
            const message = hasExistingAttachment ? 'Attachment replaced successfully!' : 'Attachment uploaded successfully!';
            showAlert('success', message);

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
        const btnText = hasExistingAttachment ? 'Replace Attachment' : 'Upload Attachment';
        const btnIcon = hasExistingAttachment ? 'sync' : 'upload';
        uploadBtn.innerHTML = `<i class="fas fa-${btnIcon}"></i> ${btnText}`;
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
