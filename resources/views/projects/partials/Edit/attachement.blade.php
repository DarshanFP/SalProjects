{{-- resources/views/projects/partials/Edit/attachement.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>3. Attachment</h4>
    </div>
    <div class="card-body">
        @if($project->attachments->isNotEmpty())
            <div class="mb-4">
                <label class="form-label">Current Attachment</label>
                <div class="border card">
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
                            <div class="mb-2 text-muted small">
                                Size: {{ number_format($fileSize / 1024, 2) }} KB
                            </div>
                            @if($attachment->description)
                                <div class="mb-2 text-muted small">
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
                            <div class="mb-2 alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>{{ e($attachment->file_name) }}</strong> (File not found)
                            </div>
                            <div class="mb-2 text-muted small">
                                <strong>Path:</strong> {{ e($attachment->file_path) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="mb-4 alert alert-info">
                <i class="fas fa-info-circle"></i> No attachment found for this project.
            </div>
        @endif

        <div class="mb-3">
            <h5>{{ $project->attachments->isNotEmpty() ? 'Replace Attachment' : 'Add Attachment' }}</h5>
            <p class="mb-3 text-muted small">
                <i class="fas fa-info-circle"></i> Select a file below to {{ $project->attachments->isNotEmpty() ? 'replace' : 'add' }} an attachment. The attachment will be saved when you click "Update Project" at the bottom of the form.
            </p>
            <div id="attachmentFields">
                <div class="mb-3">
                    <label for="file" class="form-label">Attachment File</label>
                    <label class="form-label"><i>(PDF, DOC, DOCX files are allowed, maximum 2 MB)</i></label>

                    <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror"
                           accept=".pdf, .doc, .docx" onchange="validateFile(this)">

                    <div id="file-preview" class="mt-2" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-file" id="file-icon"></i> <span id="file-name"></span>
                            <br><small>Size: <span id="file-size"></span></small>
                        </div>
                    </div>

                    <div id="file-size-warning" class="mt-2 alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i> File size must not exceed 2 MB!
                    </div>

                    <div id="file-type-warning" class="mt-2 alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i> Only PDF, DOC, and DOCX files are allowed!
                    </div>

                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="file_name" class="form-label">File Name</label>
                    <input type="text" name="file_name" id="file_name" class="form-control @error('file_name') is-invalid @enderror"
                           placeholder="Enter a descriptive name for the file"
                           value="{{ $project->attachments->isNotEmpty() ? e($project->attachments[0]->file_name) : '' }}">

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
            </div>
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
