<!-- resources/views/reports/monthly/partials/attachments.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>6. Attachments</h4>
    </div>
    <div class="card-body">
        <div id="attachments-container">
            @foreach(old('attachment_files', ['']) as $index => $value)
            <div class="mb-3 attachment-group" data-index="{{ $index }}">
                <div class="row">
                    <div class="col-md-4">
                        <label for="attachment_file_{{ $index }}" class="form-label">
                            <span class="badge bg-secondary me-2">{{ $index + 1 }}</span>
                            Attachment File {{ $index + 1 }}
                        </label>
                        <input type="file" name="attachment_files[]" id="attachment_file_{{ $index }}"
                               class="form-control @error("attachment_files.$index") is-invalid @enderror"
                               accept=".pdf, .doc, .docx, .xls, .xlsx"
                               onchange="validateAttachmentFile(this, {{ $index }})"
                               style="background-color: #202ba3;">
                        <small class="text-muted">(Max 2MB. Allowed: PDF, DOC, DOCX, XLS, XLSX)</small>
                        @error("attachment_files.$index")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="attachment_name_{{ $index }}" class="form-label">File Name</label>
                        <input type="text" name="attachment_names[]" id="attachment_name_{{ $index }}"
                               class="form-control @error("attachment_names.$index") is-invalid @enderror"
                               placeholder="Enter descriptive name"
                               value="{{ old("attachment_names.$index") }}"
                               style="background-color: #202ba3;">
                        @error("attachment_names.$index")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="attachment_description_{{ $index }}" class="form-label">Description</label>
                        <textarea name="attachment_descriptions[]" id="attachment_description_{{ $index }}"
                                  class="form-control auto-resize-textarea @error("attachment_descriptions.$index") is-invalid @enderror"
                                  rows="2"
                                  placeholder="Brief description">{{ old("attachment_descriptions.$index") }}</textarea>
                        @error("attachment_descriptions.$index")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAttachment(this)">Remove Attachment</button>
                </div>
                <div id="file-preview-{{ $index }}" class="mt-2" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-file"></i> <span id="file-name-{{ $index }}"></span>
                        <br><small>Size: <span id="file-size-{{ $index }}"></span></small>
                    </div>
                </div>
                <div id="file-error-{{ $index }}" class="mt-2 alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> <span id="error-message-{{ $index }}"></span>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-primary" onclick="addAttachment()">Add More Attachment</button>
    </div>
</div>

<script>
function validateAttachmentFile(input, index) {
    const file = input.files[0];
    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    const maxSize = 2097152; // 2MB

    // Reset displays
    document.getElementById(`file-preview-${index}`).style.display = 'none';
    document.getElementById(`file-error-${index}`).style.display = 'none';
    input.classList.remove('is-invalid');

    if (file) {
        // Check file type
        if (!validTypes.includes(file.type)) {
            showFileError(index, 'Invalid file type. Only PDF, DOC, DOCX, XLS, and XLSX files are allowed.');
            input.value = '';
            return;
        }

        // Check file size
        if (file.size > maxSize) {
            showFileError(index, 'File size must not exceed 2 MB.');
            input.value = '';
            return;
        }

        // Show file preview
        document.getElementById(`file-name-${index}`).textContent = file.name;
        document.getElementById(`file-size-${index}`).textContent = formatFileSize(file.size);
        document.getElementById(`file-preview-${index}`).style.display = 'block';
    }
}

function showFileError(index, message) {
    document.getElementById(`error-message-${index}`).textContent = message;
    document.getElementById(`file-error-${index}`).style.display = 'block';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function addAttachment() {
    const attachmentsContainer = document.getElementById('attachments-container');
    const currentAttachments = attachmentsContainer.children.length;
    const index = currentAttachments;

    if (currentAttachments < 5) { // Limit to 5 attachments
        const newAttachmentHtml = `
            <div class="mb-3 attachment-group" data-index="${index}">
                <div class="row">
                    <div class="col-md-4">
                        <label for="attachment_file_${index}" class="form-label">
                            <span class="badge bg-secondary me-2">${index + 1}</span>
                            Attachment File ${index + 1}
                        </label>
                        <input type="file" name="attachment_files[]" id="attachment_file_${index}"
                               class="form-control"
                               accept=".pdf, .doc, .docx, .xls, .xlsx"
                               onchange="validateAttachmentFile(this, ${index})"
                               style="background-color: #202ba3;">
                        <small class="text-muted">(Max 2MB. Allowed: PDF, DOC, DOCX, XLS, XLSX)</small>
                    </div>
                    <div class="col-md-4">
                        <label for="attachment_name_${index}" class="form-label">File Name</label>
                        <input type="text" name="attachment_names[]" id="attachment_name_${index}"
                               class="form-control"
                               placeholder="Enter descriptive name"
                               style="background-color: #202ba3;">
                    </div>
                    <div class="col-md-4">
                        <label for="attachment_description_${index}" class="form-label">Description</label>
                        <textarea name="attachment_descriptions[]" id="attachment_description_${index}"
                                  class="form-control auto-resize-textarea"
                                  rows="2"
                                  placeholder="Brief description"></textarea>
                    </div>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAttachment(this)">Remove Attachment</button>
                </div>
                <div id="file-preview-${index}" class="mt-2" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-file"></i> <span id="file-name-${index}"></span>
                        <br><small>Size: <span id="file-size-${index}"></span></small>
                    </div>
                </div>
                <div id="file-error-${index}" class="mt-2 alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> <span id="error-message-${index}"></span>
                </div>
            </div>
        `;
        attachmentsContainer.insertAdjacentHTML('beforeend', newAttachmentHtml);

        // Initialize auto-resize for new attachment textarea using global function
        const newAttachment = attachmentsContainer.lastElementChild;
        if (newAttachment && typeof initDynamicTextarea === 'function') {
            initDynamicTextarea(newAttachment);
        }

        reindexAttachments();
    } else {
        alert('You can upload a maximum of 5 attachments.');
    }
}

function removeAttachment(button) {
    const attachmentGroup = button.closest('.attachment-group');
    attachmentGroup.remove();
    reindexAttachments();
}

/**
 * Reindex all attachment groups after add/remove operations
 * Updates index badges, data-index attributes, form field names, and associated element IDs
 * Ensures sequential numbering (1, 2, 3, ...) for all attachment groups
 */
/**
 * Reindexes all attachment entries after add/remove operations
 * Updates index badges, data-index attributes, and form field names/IDs
 * Ensures sequential numbering (1, 2, 3, ...) for all attachment entries
 *
 * @returns {void}
 */
function reindexAttachments() {
    const attachmentGroups = document.querySelectorAll('.attachment-group');
    attachmentGroups.forEach((group, index) => {
        // Update data-index
        group.dataset.index = index;

        // Update badge and label
        const label = group.querySelector('label[for^="attachment_file"]');
        if (label) {
            label.innerHTML = `<span class="badge bg-secondary me-2">${index + 1}</span>Attachment File ${index + 1}`;
        }

        // Update all IDs that use the index
        const fileInput = group.querySelector('input[type="file"]');
        const nameInput = group.querySelector('input[name^="attachment_names"]');
        const descriptionTextarea = group.querySelector('textarea[name^="attachment_descriptions"]');
        const filePreview = group.querySelector('[id^="file-preview-"]');
        const fileError = group.querySelector('[id^="file-error-"]');
        const fileName = group.querySelector('[id^="file-name-"]');
        const fileSize = group.querySelector('[id^="file-size-"]');
        const errorMessage = group.querySelector('[id^="error-message-"]');

        if (fileInput) {
            fileInput.id = `attachment_file_${index}`;
            fileInput.name = `attachment_files[]`;
            fileInput.setAttribute('onchange', `validateAttachmentFile(this, ${index})`);
            const labelFor = group.querySelector('label[for^="attachment_file"]');
            if (labelFor) {
                labelFor.setAttribute('for', `attachment_file_${index}`);
            }
        }

        if (nameInput) {
            nameInput.id = `attachment_name_${index}`;
            nameInput.name = `attachment_names[]`;
            const labelFor = group.querySelector('label[for^="attachment_name"]');
            if (labelFor) {
                labelFor.setAttribute('for', `attachment_name_${index}`);
            }
        }

        if (descriptionTextarea) {
            descriptionTextarea.id = `attachment_description_${index}`;
            descriptionTextarea.name = `attachment_descriptions[]`;
            const labelFor = group.querySelector('label[for^="attachment_description"]');
            if (labelFor) {
                labelFor.setAttribute('for', `attachment_description_${index}`);
            }
        }

        if (filePreview) {
            filePreview.id = `file-preview-${index}`;
        }

        if (fileError) {
            fileError.id = `file-error-${index}`;
        }

        if (fileName) {
            fileName.id = `file-name-${index}`;
        }

        if (fileSize) {
            fileSize.id = `file-size-${index}`;
        }

        if (errorMessage) {
            errorMessage.id = `error-message-${index}`;
        }
    });

    // Re-initialize all textareas after reindexing to maintain auto-resize
    if (typeof autoResizeTextarea === 'function') {
        document.querySelectorAll('#attachments-container .auto-resize-textarea').forEach(textarea => {
            autoResizeTextarea(textarea);
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    reindexAttachments();
});
</script>
