<!-- resources/views/reports/monthly/partials/attachments.blade.php -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachment</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="file" class="form-label">Attachment File</label>
            <input type="file" name="file" id="file" class="mb-2 form-control" accept=".pdf, .doc, .docx, .xls, .xlsx" required onchange="checkFile(this)" style="background-color: #202ba3;">
            {{-- Warning --}}
        <p id="file-size-warning" style="color: red; display: none;">File size must not exceed 10 MB!</p>
        <p id="file-type-warning" style="color: red; display: none;">Only PDF, DOC, DOCX, XLS, and XLSX files are allowed!</p>

            <label for="file_name" class="form-label">File Name</label>
            <input type="text" name="file_name" class="mb-2 form-control" placeholder="Name of File Attached" required style="background-color: #202ba3;">

            <label for="description" class="form-label">Brief Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Describe the file" required style="background-color: #202ba3;"></textarea>
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
</script>
