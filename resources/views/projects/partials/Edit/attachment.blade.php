{{-- resources/views/projects/partials/Edit/attachment.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>3. Attachment</h4>
    </div>
    <div class="card-body">
        @if($project->attachments->isNotEmpty())
            <div class="mb-4">
                <label class="form-label">Current Attachments</label>
                @foreach($project->attachments as $index => $attachment)
                    <div class="border card mb-3" id="common-attachment-{{ $attachment->id }}">
                        <div class="card-body">
                            @php
                                $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path);
                                $fileSize = $fileExists ? \Illuminate\Support\Facades\Storage::disk('public')->size($attachment->file_path) : 0;
                                $fileExtension = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                                $fileIcon = config("attachments.file_icons.{$fileExtension}", config('attachments.file_icons.default'));
                            @endphp

                            @if($fileExists)
                                <div class="mb-2">
                                    <i class="{{ $fileIcon }}"></i>
                                    <strong>{{ e($attachment->file_name) }}</strong>
                                </div>
                                <div class="mb-2 text-muted small">
                                    Size: {{ format_indian($fileSize / 1024, 2) }} KB
                                </div>
                                @if($attachment->description)
                                    <div class="mb-2 text-muted small">
                                        <strong>Description:</strong> {{ e($attachment->description) }}
                                    </div>
                                @endif
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('projects.attachments.view', $attachment->id) }}"
                                       target="_blank"
                                       class="btn btn-outline-primary"
                                       title="View file">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('projects.attachments.download', $attachment->id) }}"
                                       class="btn btn-outline-success"
                                       title="Download file">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="confirmRemoveCommonAttachment({{ $attachment->id }}, {{ json_encode($attachment->file_name) }})">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            @else
                                <div class="mb-2 alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> File not found in storage
                                </div>
                                <div class="mb-2 text-muted small">
                                    File name: {{ e($attachment->file_name) }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mb-4 alert alert-info">
                <i class="fas fa-info-circle"></i> No attachments uploaded yet.
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label">Add New Attachment</label>
            <p class="mb-3 text-muted small">
                Upload additional files. Existing attachments will be preserved.
            </p>
            <div class="mb-3">
                <label for="file" class="form-label">Attachment File</label>
                    <label class="form-label">
                        <i>
                            @php
                                $allowedTypes = config('attachments.allowed_types.project_attachments');
                                $typesList = implode(', ', array_map('strtoupper', $allowedTypes['extensions']));
                                $maxSizeMB = config('attachments.display_max_size_mb');
                            @endphp
                            ({{ $typesList }} files are allowed, maximum {{ $maxSizeMB }} MB)
                        </i>
                    </label>

                <input type="file" name="file" id="file" class="mb-2 form-control @error('file') is-invalid @enderror"
                       accept=".pdf, .doc, .docx" onchange="validateFile(this)">

                <div id="file-preview" class="mt-2" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-file" id="file-icon"></i> <span id="file-name"></span>
                        <br><small>Size: <span id="file-size"></span></small>
                    </div>
                </div>

                <div id="file-size-warning" class="mt-2 alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i> {{ str_replace(':size', config('attachments.display_max_size_mb'), config('attachments.error_messages.file_size_exceeded')) }}!
                </div>

                <div id="file-type-warning" class="mt-2 alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        @php
                            $allowedTypes = config('attachments.allowed_types.project_attachments');
                            $typesList = implode(', ', array_map('strtoupper', $allowedTypes['extensions']));
                        @endphp
                        {{ str_replace(':types', $typesList, config('attachments.error_messages.file_type_invalid')) }}!
                </div>

                @error('file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="file_name" class="form-label">File Name</label>
                <input type="text" name="file_name" id="file_name" class="mb-2 form-control @error('file_name') is-invalid @enderror"
                       placeholder="Enter a descriptive name for the file" value="{{ old('file_name') }}">

                @error('file_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="attachment_description" class="form-label">Brief Description</label>
                <textarea name="attachment_description" id="attachment_description" class="form-control sustainability-textarea @error('attachment_description') is-invalid @enderror"
                          rows="3" placeholder="Describe the content and purpose of this file">{{ old('attachment_description') }}</textarea>

                @error('attachment_description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/attachments-validation.js') }}"></script>
<script>
function validateFile(input) {
    const file = input.files[0];
    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const maxSize = 7340032; // 7MB (server allows up to 7MB, but we display 5MB to users)
    const displayMaxSize = 5242880; // 5MB - for display purposes only

    // Reset warnings with null checks
    const sizeWarning = document.getElementById('file-size-warning');
    const typeWarning = document.getElementById('file-type-warning');
    const preview = document.getElementById('file-preview');

    if (sizeWarning) sizeWarning.style.display = 'none';
    if (typeWarning) typeWarning.style.display = 'none';
    if (preview) preview.style.display = 'none';

    if (file) {
        // Check file type
        if (!validTypes.includes(file.type)) {
            if (typeWarning) typeWarning.style.display = 'block';
            input.value = '';
            return;
        }

        // Check file size
        if (file.size > maxSize) {
            if (sizeWarning) sizeWarning.style.display = 'block';
            input.value = '';
            return;
        }

        // Show file preview with appropriate icon (with null checks)
        const fileNameEl = document.getElementById('file-name');
        const fileSizeEl = document.getElementById('file-size');
        const fileIcon = document.getElementById('file-icon');

        if (fileNameEl) fileNameEl.textContent = file.name;
        if (fileSizeEl) fileSizeEl.textContent = formatFileSize(file.size);

        // Set appropriate icon based on file type
        if (fileIcon) {
            if (file.type === 'application/pdf') {
                fileIcon.className = 'fas fa-file-pdf text-danger';
            } else if (file.type === 'application/msword') {
                fileIcon.className = 'fas fa-file-word text-primary';
            } else if (file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                fileIcon.className = 'fas fa-file-word text-primary';
            } else {
                fileIcon.className = 'fas fa-file text-secondary';
            }
        }

        if (preview) preview.style.display = 'block';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function confirmRemoveCommonAttachment(id, name) {
    if (!confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
        return;
    }
    removeCommonAttachment(id);
}

function removeCommonAttachment(id) {
    let urlTemplate = "{{ route('projects.attachments.files.destroy', ':id') }}";
    let deleteUrl = urlTemplate.replace(':id', id);

    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        return response.json().then(data => {
            if (!response.ok) {
                throw new Error(data.message || 'Delete failed.');
            }
            return data;
        });
    })
    .then(data => {
        if (data.success) {
            const row = document.getElementById('common-attachment-' + id);
            if (row) row.remove();
        }
    })
    .catch(error => {
        alert(error.message);
        console.error(error);
    });
}
</script>
