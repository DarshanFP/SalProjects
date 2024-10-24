{{-- resources/views/projects/partials/IAH/documents.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Please attach the following documents of the beneficiary</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="aadhar_copy" class="form-label">Aadhar Copy:</label>
            <input type="file" name="aadhar_copy" class="form-control">
        </div>

        <div class="mb-3">
            <label for="request_letter" class="form-label">Request Letter:</label>
            <input type="file" name="request_letter" class="form-control">
        </div>

        <div class="mb-3">
            <label for="medical_reports" class="form-label">Medical Reports (Diagnosis):</label>
            <input type="file" name="medical_reports" class="form-control">
        </div>

        <div class="mb-3">
            <label for="other_docs" class="form-label">Any Other Supporting Documents:</label>
            <input type="file" name="other_docs" class="form-control">
        </div>
    </div>
</div>
