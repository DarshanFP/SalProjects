{{-- resources/views/projects/partials/Edit/IAH/documents.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Attached Documents of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @if($project->iahDocuments)
            @php
                $documents = $project->iahDocuments;
            @endphp
        @else
            @php
                $documents = new \App\Models\OldProjects\IAH\ProjectIAHDocuments();
            @endphp
        @endif

        <!-- Aadhar Copy -->
        <div class="mb-3">
            <label for="aadhar_copy" class="form-label">Aadhar Copy:</label>
            <input type="file" name="aadhar_copy" class="form-control">
            @if($documents->aadhar_copy)
                <p><small>Current file: <a href="{{ asset('storage/' . $documents->aadhar_copy) }}" target="_blank">View Aadhar Copy</a></small></p>
            @endif
        </div>

        <!-- Request Letter -->
        <div class="mb-3">
            <label for="request_letter" class="form-label">Request Letter:</label>
            <input type="file" name="request_letter" class="form-control">
            @if($documents->request_letter)
                <p><small>Current file: <a href="{{ asset('storage/' . $documents->request_letter) }}" target="_blank">View Request Letter</a></small></p>
            @endif
        </div>

        <!-- Medical Reports -->
        <div class="mb-3">
            <label for="medical_reports" class="form-label">Medical Reports (Diagnosis):</label>
            <input type="file" name="medical_reports" class="form-control">
            @if($documents->medical_reports)
                <p><small>Current file: <a href="{{ asset('storage/' . $documents->medical_reports) }}" target="_blank">View Medical Reports</a></small></p>
            @endif
        </div>

        <!-- Other Supporting Documents -->
        <div class="mb-3">
            <label for="other_docs" class="form-label">Any Other Supporting Documents:</label>
            <input type="file" name="other_docs" class="form-control">
            @if($documents->other_docs)
                <p><small>Current file: <a href="{{ asset('storage/' . $documents->other_docs) }}" target="_blank">View Other Documents</a></small></p>
            @endif
        </div>
    </div>
</div>
