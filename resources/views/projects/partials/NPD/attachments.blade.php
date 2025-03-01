<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachments</h4>
    </div>
    <div class="card-body">
        <div id="attachments-container">
            @if(!empty($predecessorAttachments))
                @foreach($predecessorAttachments as $index => $attachment)
                    <div class="mb-3 attachment-group" data-index="{{ $index }}">
                        <label for="attachments[{{ $index }}][file]" class="form-label">Attachment {{ $index + 1 }} (Existing File)</label>
                        <input type="text" name="attachments[{{ $index }}][file]" class="form-control readonly-input mb-2" value="{{ $attachment['file'] }}" readonly>

                        <label for="attachments[{{ $index }}][file_name]" class="form-label">File Name</label>
                        <input type="text" name="attachments[{{ $index }}][file_name]" class="form-control mb-2" value="{{ $attachment['file_name'] }}" placeholder="Name of File Attached" style="background-color: #202ba3;">

                        <label for="attachments[{{ $index }}][description]" class="form-label">Brief Description</label>
                        <textarea name="attachments[{{ $index }}][description]" class="form-control" rows="3" placeholder="Describe the file" style="background-color: #202ba3;">{{ $attachment['description'] }}</textarea>

                        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeAttachment(this)">Remove</button>
                    </div>
                @endforeach
            @endif

            <!-- New Attachment Template -->
            <div class="mb-3 attachment-group" data-index="0">
                <label for="attachments[0][file]" class="form-label">Attachment File</label>
                <input type="file" name="attachments[0][file]" class="form-control mb-2" accept=".pdf" onchange="checkFileSize(this)" style="background-color: #202ba3;">

                <label for="attachments[0][file_name]" class="form-label">File Name</label>
                <input type="text" name="attachments[0][file_name]" class="form-control mb-2" placeholder="Name of File Attached" style="background-color: #202ba3;">

                <label for="attachments[0][description]" class="form-label">Brief Description</label>
                <textarea name="attachments[0][description]" class="form-control" rows="3" placeholder="Describe the file" style="background-color: #202ba3;"></textarea>

                <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeAttachment(this)">Remove</button>
            </div>
        </div>

        <button type="button" class="btn btn-primary mt-3" onclick="addAttachment()">Add More Attachments</button>
        <p id="file-size-warning" style="color: red; display: none;">File size must not exceed 10 MB!</p>
    </div>
</div>

<script>
let attachmentIndex = {{ isset($predecessorAttachments) ? count($predecessorAttachments) : 1 }};

function checkFileSize(input) {
    const file = input.files[0];
    if (file && file.size > 10485760) { // 10 MB in bytes
        document.getElementById('file-size-warning').style.display = 'block';
        input.value = ''; // Reset the file input
    } else {
        document.getElementById('file-size-warning').style.display = 'none';
    }
}

// Add a new attachment field dynamically
function addAttachment() {
    const attachmentsContainer = document.getElementById('attachments-container');
    const newAttachment = document.createElement('div');
    newAttachment.className = 'mb-3 attachment-group';
    newAttachment.dataset.index = attachmentIndex;

    newAttachment.innerHTML = `
        <label for="attachments[\${attachmentIndex}][file]" class="form-label">Attachment \${attachmentIndex + 1}</label>
        <input type="file" name="attachments[\${attachmentIndex}][file]" class="form-control mb-2" accept=".pdf" onchange="checkFileSize(this)" style="background-color: #202ba3;">

        <label for="attachments[\${attachmentIndex}][file_name]" class="form-label">File Name</label>
        <input type="text" name="attachments[\${attachmentIndex}][file_name]" class="form-control mb-2" placeholder="Name of File Attached" style="background-color: #202ba3;">

        <label for="attachments[\${attachmentIndex}][description]" class="form-label">Brief Description</label>
        <textarea name="attachments[\${attachmentIndex}][description]" class="form-control" rows="3" placeholder="Describe the file" style="background-color: #202ba3;"></textarea>

        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeAttachment(this)">Remove</button>
    `;

    attachmentsContainer.appendChild(newAttachment);
    attachmentIndex++;
}

// Remove an attachment field dynamically
function removeAttachment(button) {
    const attachmentGroup = button.closest('.attachment-group');
    attachmentGroup.remove();
}
</script>

<style>
    .readonly-input {
        background-color: #e9ecef !important;
        pointer-events: none;
    }
</style>
