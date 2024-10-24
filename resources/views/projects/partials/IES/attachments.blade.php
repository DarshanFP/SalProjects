<div class="mb-3 card">
    <div class="card-header">
        <h4>Please Attach the Following Documents</h4>
    </div>
    <div class="card-body">
        <!-- Aadhar Card -->
        <div class="form-group">
            <label>Aadhar Card (true copy):</label>
            <input type="file" name="aadhar_card" class="form-control-file" onchange="renameFile(this, 'aadhar_card')" accept=".pdf,.jpg,.jpeg,.png">
        </div>

        <!-- Fee Quotation -->
        <div class="form-group">
            <label>Fee Quotation from the Educational Institution (original):</label>
            <input type="file" name="fee_quotation" class="form-control-file" onchange="renameFile(this, 'fee_quotation')" accept=".pdf,.jpg,.jpeg,.png">
        </div>

        <!-- Proof of Scholarship -->
        <div class="form-group">
            <label>Proof of Scholarship Received Previous Year:</label>
            <input type="file" name="scholarship_proof" class="form-control-file" onchange="renameFile(this, 'scholarship_proof')" accept=".pdf,.jpg,.jpeg,.png">
        </div>

        <!-- Medical Confirmation -->
        <div class="form-group">
            <label>Medical Confirmation (in case of ill health of parents - original):</label>
            <input type="file" name="medical_confirmation" class="form-control-file" onchange="renameFile(this, 'medical_confirmation')" accept=".pdf,.jpg,.jpeg,.png">
        </div>

        <!-- Caste Certificate -->
        <div class="form-group">
            <label>Caste Certificate (true copy):</label>
            <input type="file" name="caste_certificate" class="form-control-file" onchange="renameFile(this, 'caste_certificate')" accept=".pdf,.jpg,.jpeg,.png">
        </div>

        <!-- Self Declaration -->
        <div class="form-group">
            <label>Self Declaration (in case of single parent - original):</label>
            <input type="file" name="self_declaration" class="form-control-file" onchange="renameFile(this, 'self_declaration')" accept=".pdf,.jpg,.jpeg,.png">
        </div>

        <!-- Death Certificate -->
        <div class="form-group">
            <label>Death Certificate (in case of deceased parents - true copy):</label>
            <input type="file" name="death_certificate" class="form-control-file" onchange="renameFile(this, 'death_certificate')" accept=".pdf,.jpg,.jpeg,.png">
        </div>

        <!-- Request Letter -->
        <div class="form-group">
            <label>Request Letter (original copy):</label>
            <input type="file" name="request_letter" class="form-control-file" onchange="renameFile(this, 'request_letter')" accept=".pdf,.jpg,.jpeg,.png">
        </div>
    </div>
</div>

<!-- JavaScript to rename files based on project_id and label name -->
<script>
    (function(){
    function renameFile(input, label) {
        const projectId = document.querySelector('input[name="project_id"]').value;
        const file = input.files[0];
        const extension = file.name.split('.').pop();
        const newFileName = `${projectId}_${label}.${extension}`;

        // This sets the new file name for server-side processing.
        const dataTransfer = new DataTransfer();
        const renamedFile = new File([file], newFileName, {type: file.type});
        dataTransfer.items.add(renamedFile);
        input.files = dataTransfer.files;
    }
})();
</script>

<!-- Styles -->
<style>
    .form-control-file {
        background-color: #202ba3;
        color: white;
    }
</style>
