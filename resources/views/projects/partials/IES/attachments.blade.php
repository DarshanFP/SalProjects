<div class="mb-3 card">
    <div class="card-header">
        <h4>Please Attach the Following Documents</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
                <!-- Aadhar Card -->
                <div class="form-group">
                    <label>Aadhar Card (true copy):</label><br>
                    <input type="file" name="aadhar_card" class="form-control-file" onchange="IESrenameFile(this, 'aadhar')" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <!-- Fee Quotation -->
                <div class="form-group">
                    <label>Fee Quotation from Educational Institution (original):</label>
                    <input type="file" name="fee_quotation" class="form-control-file" onchange="IESrenameFile(this, 'fee_quotation')" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <!-- Proof of Scholarship -->
                <div class="form-group">
                    <label>Proof of Scholarship Received Previous Year:</label>
                    <input type="file" name="scholarship_proof" class="form-control-file" onchange="IESrenameFile(this, 'scholarship')" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <!-- Medical Confirmation -->
                <div class="form-group">
                    <label>Medical Confirmation (ill health of parents - original):</label>
                    <input type="file" name="medical_confirmation" class="form-control-file" onchange="IESrenameFile(this, 'medical')" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <!-- Caste Certificate -->
                <div class="form-group">
                    <label>Caste Certificate (true copy):</label>
                    <input type="file" name="caste_certificate" class="form-control-file" onchange="IESrenameFile(this, 'caste')" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <!-- Self Declaration -->
                <div class="form-group">
                    <label>Self Declaration (single parent - original):</label>
                    <input type="file" name="self_declaration" class="form-control-file" onchange="IESrenameFile(this, 'declaration')" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <!-- Death Certificate -->
                <div class="form-group">
                    <label>Death Certificate (deceased parents - true copy):</label>
                    <input type="file" name="death_certificate" class="form-control-file" onchange="IESrenameFile(this, 'death')" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <!-- Request Letter -->
                <div class="form-group">
                    <label>Request Letter (original copy):</label>
                    <input type="file" name="request_letter" class="form-control-file" onchange="IESrenameFile(this, 'request')" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to rename files based on project_id and label name -->
<script>
    (function() {
    function IESrenameFile(input, shortName) {
        const projectId = document.querySelector('input[name="project_id"]').value;
        const file = input.files[0];
        const extension = file.name.split('.').pop();
        const newFileName = `${projectId}_${shortName}.${extension}`;

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
    .form-group {
        margin-bottom: 20px; /* Add spacing between fields */
    }

    .form-control-file {
        max-width: 100%; /* Ensure full width in each column */
        background-color: #202ba3;
        color: white;
    }

    label {
        margin-bottom: 5px; /* Add space between label and input */
    }

    .card-body {
        padding: 20px;
    }

    .row {
        margin: 0; /* Ensure the row uses the full width */
    }

    .col-md-6 {
        padding: 10px; /* Add spacing between columns */
    }
</style>
