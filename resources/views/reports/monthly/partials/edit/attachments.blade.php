{{-- resources/views/reports/monthly/partials/edit/attachments.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>6. Attachments</h4>
    </div>
    <div class="card-body">
        {{-- Display Existing Attachments --}}
        @if($report->attachments && $report->attachments->count() > 0)
            <div class="mb-4">
                <label class="form-label">Existing Attachments</label>
                <div class="row">
                    @foreach($report->attachments as $attachment)
                    <div class="mb-3 col-md-6 attachment-item">
                        <div class="border card">
                            <div class="card-body">
                                @php
                                    $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path);
                                    $fileSize = $fileExists ? \Illuminate\Support\Facades\Storage::disk('public')->size($attachment->file_path) : 0;
                                @endphp

                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        @if($fileExists)
                                            <div class="mb-2">
                                                <i class="fas fa-file text-primary"></i>
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
                                                <a href="{{ route('monthly.report.downloadAttachment', $attachment->id) }}"
                                                   class="btn btn-outline-success"
                                                   title="Download file">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                                <button type="button"
                                                        class="btn btn-outline-danger"
                                                        onclick="confirmRemoveAttachment({{ $attachment->id }}, '{{ e($attachment->file_name) }}')"
                                                        title="Remove attachment">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </div>
                                        @else
                                            <div class="mb-2 alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>{{ e($attachment->file_name) }}</strong> (File not found)
                                            </div>
                                            <div class="mb-2 text-muted small">
                                                <strong>Path:</strong> {{ e($attachment->file_path) }}
                                            </div>
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="confirmRemoveAttachment({{ $attachment->id }}, '{{ e($attachment->file_name) }}')"
                                                    title="Remove broken attachment">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="alert alert-info no-attachments-message">
                <i class="fas fa-info-circle"></i> No attachments found for this report.
            </div>
        @endif

        {{-- Add New Attachments --}}
        <div class="mb-3">
            <h5>Add New Attachments</h5>
            <div id="new-attachments-container">
                @foreach(old('new_attachment_files', ['']) as $index => $value)
                <div class="mb-3 new-attachment-group" data-index="{{ $index }}">
                    <div class="mb-3">
                        <label for="new_attachment_file_{{ $index }}" class="form-label">New Attachment File {{ $index + 1 }}</label>
                        <input type="file" name="new_attachment_files[]" id="new_attachment_file_{{ $index }}"
                               class="form-control @error("new_attachment_files.$index") is-invalid @enderror"
                               accept=".pdf, .doc, .docx, .xls, .xlsx"
                               onchange="validateNewAttachmentFile(this, {{ $index }})"
                               style="background-color: #202ba3;">
                        <small class="text-muted">(Max 2MB. Allowed: PDF, DOC, DOCX, XLS, XLSX)</small>
                        @error("new_attachment_files.$index")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="new_attachment_name_{{ $index }}" class="form-label">File Name</label>
                        <input type="text" name="new_attachment_names[]" id="new_attachment_name_{{ $index }}"
                               class="form-control @error("new_attachment_names.$index") is-invalid @enderror"
                               placeholder="Enter descriptive name"
                               value="{{ old("new_attachment_names.$index") }}"
                               style="background-color: #202ba3;">
                        @error("new_attachment_names.$index")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="new_attachment_description_{{ $index }}" class="form-label">Description</label>
                        <textarea name="new_attachment_descriptions[]" id="new_attachment_description_{{ $index }}"
                                  class="form-control @error("new_attachment_descriptions.$index") is-invalid @enderror"
                                  rows="2"
                                  placeholder="Brief description">{{ old("new_attachment_descriptions.$index") }}</textarea>
                        @error("new_attachment_descriptions.$index")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeNewAttachment(this)">Remove</button>
                    </div>
                    <div id="new-file-preview-{{ $index }}" class="mt-2" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-file"></i> <span id="new-file-name-{{ $index }}"></span>
                            <br><small>Size: <span id="new-file-size-{{ $index }}"></span></small>
                        </div>
                    </div>
                    <div id="new-file-error-{{ $index }}" class="mt-2 alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i> <span id="new-error-message-{{ $index }}"></span>
                    </div>
                </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-primary" onclick="addNewAttachment()">Add New Attachment</button>
        </div>
    </div>
</div>

<script>
function validateNewAttachmentFile(input, index) {
    const file = input.files[0];
    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    const maxSize = 2097152; // 2MB

    // Reset displays
    document.getElementById(`new-file-preview-${index}`).style.display = 'none';
    document.getElementById(`new-file-error-${index}`).style.display = 'none';
    input.classList.remove('is-invalid');

    if (file) {
        // Check file type
        if (!validTypes.includes(file.type)) {
            showNewFileError(index, 'Invalid file type. Only PDF, DOC, DOCX, XLS, and XLSX files are allowed.');
            input.value = '';
            return;
        }

        // Check file size
        if (file.size > maxSize) {
            showNewFileError(index, 'File size must not exceed 2 MB.');
            input.value = '';
            return;
        }

        // Show file preview
        document.getElementById(`new-file-name-${index}`).textContent = file.name;
        document.getElementById(`new-file-size-${index}`).textContent = formatFileSize(file.size);
        document.getElementById(`new-file-preview-${index}`).style.display = 'block';
    }
}

function showNewFileError(index, message) {
    document.getElementById(`new-error-message-${index}`).textContent = message;
    document.getElementById(`new-file-error-${index}`).style.display = 'block';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function addNewAttachment() {
    const attachmentsContainer = document.getElementById('new-attachments-container');
    const currentAttachments = attachmentsContainer.children.length;
    const index = currentAttachments;

    if (currentAttachments < 5) { // Limit to 5 new attachments
        const newAttachmentHtml = `
            <div class="mb-3 new-attachment-group" data-index="${index}">
                <div class="mb-3">
                    <label for="new_attachment_file_${index}" class="form-label">New Attachment File ${index + 1}</label>
                    <input type="file" name="new_attachment_files[]" id="new_attachment_file_${index}"
                           class="form-control"
                           accept=".pdf, .doc, .docx, .xls, .xlsx"
                           onchange="validateNewAttachmentFile(this, ${index})"
                           style="background-color: #202ba3;">
                    <small class="text-muted">(Max 2MB. Allowed: PDF, DOC, DOCX, XLS, XLSX)</small>
                </div>
                <div class="mb-3">
                    <label for="new_attachment_name_${index}" class="form-label">File Name</label>
                    <input type="text" name="new_attachment_names[]" id="new_attachment_name_${index}"
                           class="form-control"
                           placeholder="Enter descriptive name"
                           style="background-color: #202ba3;">
                </div>
                <div class="mb-3">
                    <label for="new_attachment_description_${index}" class="form-label">Description</label>
                    <textarea name="new_attachment_descriptions[]" id="new_attachment_description_${index}"
                              class="form-control"
                              rows="2"
                              placeholder="Brief description"></textarea>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeNewAttachment(this)">Remove</button>
                </div>
                <div id="new-file-preview-${index}" class="mt-2" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-file"></i> <span id="new-file-name-${index}"></span>
                        <br><small>Size: <span id="new-file-size-${index}"></span></small>
                    </div>
                </div>
                <div id="new-file-error-${index}" class="mt-2 alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> <span id="new-error-message-${index}"></span>
                </div>
            </div>
        `;
        attachmentsContainer.insertAdjacentHTML('beforeend', newAttachmentHtml);
        updateNewAttachmentLabels();
    } else {
        alert('You can add a maximum of 5 new attachments.');
    }
}

function removeNewAttachment(button) {
    const attachmentGroup = button.closest('.new-attachment-group');
    attachmentGroup.remove();
    updateNewAttachmentLabels();
}

function updateNewAttachmentLabels() {
    const attachmentGroups = document.querySelectorAll('.new-attachment-group');
    attachmentGroups.forEach((group, index) => {
        const label = group.querySelector('label');
        label.textContent = `New Attachment File ${index + 1}`;
    });
}

function confirmRemoveAttachment(attachmentId, fileName) {
    if (confirm(`Are you sure you want to remove the attachment "${fileName}"? This action cannot be undone.`)) {
        removeAttachment(attachmentId);
    }
}

function removeAttachment(attachmentId) {
    // Show loading state
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Removing...';

    const url = "{{ route('attachments.remove', ':id') }}".replace(':id', attachmentId);

    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}",
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the attachment element from the DOM
            const attachmentElement = button.closest('.attachment-item');
            if (attachmentElement) {
                attachmentElement.remove();
            }

            // Show success message
            alert('Attachment removed successfully!');

            // Check if there are any attachments left
            const remainingAttachments = document.querySelectorAll('.attachment-item');
            if (remainingAttachments.length === 0) {
                // If no attachments left, show the "no attachments" message
                const noAttachmentsDiv = document.querySelector('.no-attachments-message');
                if (noAttachmentsDiv) {
                    noAttachmentsDiv.style.display = 'block';
                }
            }
        } else {
            alert(data.message || 'Failed to remove the attachment.');
            // Reset button state
            button.disabled = false;
            button.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error removing attachment:', error);
        alert('An error occurred while removing the attachment. Please try again.');
        // Reset button state
        button.disabled = false;
        button.textContent = originalText;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateNewAttachmentLabels();
});
</script>
