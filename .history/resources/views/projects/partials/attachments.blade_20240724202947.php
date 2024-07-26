<!-- resources/views/projects/partials/attachments.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>3. Attachments</h4>
    </div>
    <div class="card-body">
        <div id="attachments-container">
            <div class="mb-3 attachment-group" data-index="0">
                <label for="attachments[0][file]" class="form-label">Attachment 1</label>
                <input type="file" name="attachments[0][file]" class="mb-2 form-control" accept=".pdf">
                <label for="file_name[0]" class="form-label">File Name</label>
                <input type="text" name="file_name[0]" class="mb-2 form-control" placeholder="Name of File Attached">
                <textarea name="attachments[0][description]" class="form-control" rows="3" placeholder="Brief Description"></textarea>
                <button type="button" class="mt-2 btn btn-danger" onclick="removeAttachment(this)">Remove</button>
            </div>
        </div>
        <button type="button" class="mt-3 btn btn-primary" id="add-attachment-button" onclick="addAttachment()">Add More Attachment</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initial update of attachment labels
    updateAttachmentLabels();

    // Event listener for adding attachment
    document.getElementById('attachments-container').addEventListener('click', function(event) {
        if (event.target && event.target.matches("button.btn-danger")) {
            removeAttachment(event.target);
        }
    });

    document.getElementById('add-attachment-button').addEventListener('click', function() {
        addAttachment();
    });
});

// Add a new attachment field
function addAttachment() {
    const attachmentsContainer = document.getElementById('attachments-container');
    const currentAttachments = attachmentsContainer.children.length;

    const index = currentAttachments;
    const attachmentTemplate = `
        <div class="mb-3 attachment-group" data-index="${index}">
            <label for="attachments[${index}][file]" class="form-label">Attachment ${index + 1}</label>
<input type="file" name="attachments[0][file]" class="mb-2 form-control" accept=".pdf">
            <label for="file_name[${index}]" class="form-label">File Name</label>
            <input type="text" name="file_name[${index}]" class="mb-2 form-control" placeholder="Name of File Attached">
            <textarea name="attachments[${index}][description]" class="form-control" rows="3" placeholder="Brief Description"></textarea>
            <button type="button" class="mt-2 btn btn-danger" onclick="removeAttachment(this)">Remove</button>
        </div>
    `;
    attachmentsContainer.insertAdjacentHTML('beforeend', attachmentTemplate);
    updateAttachmentLabels();
}

// Remove an attachment field
function removeAttachment(button) {
    const attachmentGroup = button.closest('.attachment-group');
    attachmentGroup.remove();
    updateAttachmentLabels();
}

// Update the labels for the attachments
function updateAttachmentLabels() {
    const attachmentGroups = document.querySelectorAll('.attachment-group');
    attachmentGroups.forEach((group, index) => {
        const label = group.querySelector('label');
        label.textContent = `Attachment ${index + 1}`;
    });
}
</script>
