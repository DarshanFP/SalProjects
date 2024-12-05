{{-- resources/views/reports/monthly/partials/edit/attachments.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachment</h4>
    </div>
    <div class="card-body">
        {{-- Display Existing Attachments --}}
        @if($report->attachments && $report->attachments->count() > 0)
            <div class="mb-3">
                <label class="form-label">Existing Attachments</label>
                <ul>
                    @foreach($report->attachments as $attachment)
                    <div class="card-body">
                        <li>
                            <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">{{ $attachment->file_name }}</a>
                            <button type="button" class="btn btn-danger btn-sm ms-2" onclick="removeAttachment({{ $attachment->id }})">Remove</button><br><br>
                            <span class="ms-2">({{ $attachment->description ?? 'No description provided' }})</span>

                        </li>
                    </div>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Add New Attachment --}}

            <div class="mb-3">
                <label for="file" class="form-label">Add New Attachment File</label>
                <input type="file" name="file" id="file" class="mb-2 form-control" accept=".pdf, .doc, .docx, .xls, .xlsx" onchange="checkFile(this)" style="background-color: #202ba3;">
                <p id="file-size-warning" style="color: red; display: none;">File size must not exceed 10 MB!</p>
                <p id="file-type-warning" style="color: red; display: none;">Only PDF, DOC, DOCX, XLS, and XLSX files are allowed!</p>

                <label for="file_name" class="form-label">File Name</label>
                <input type="text" name="file_name" class="mb-2 form-control" placeholder="Name of File Attached" style="background-color: #202ba3;">

                <label for="description" class="form-label">Brief Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Describe the file" style="background-color: #202ba3;"></textarea>
            </div>


    </div>
</div>


<script>
function checkFile(input) {
    const file = input.files[0];
    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

    if (file) {
        // Check file type
        if (!validTypes.includes(file.type)) {
            document.getElementById('file-type-warning').style.display = 'block';
            input.value = ''; // Reset the file input
            document.getElementById('file-size-warning').style.display = 'none';
            return;
        } else {
            document.getElementById('file-type-warning').style.display = 'none';
        }

        // Check file size
        if (file.size > 10485760) { // 10 MB in bytes
            document.getElementById('file-size-warning').style.display = 'block';
            input.value = ''; // Reset the file input
        } else {
            document.getElementById('file-size-warning').style.display = 'none';
        }
    }
}

function removeAttachment(attachmentId) {
    if (confirm('Are you sure you want to remove this attachment?')) {
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
                location.reload(); // Reload the page to reflect changes
            } else {
                alert(data.message || 'Failed to remove the attachment.');
            }
        })
        .catch(error => {
            console.error('Error removing attachment:', error);
            alert('An error occurred while removing the attachment.');
        });
    }
}

</script>
