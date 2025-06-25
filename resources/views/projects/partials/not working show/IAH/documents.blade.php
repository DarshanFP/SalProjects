{{-- resources/views/projects/partials/Show/IAH/documents.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Attached Documents of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @if($IAHDocuments)
            @php
                $documents = $IAHDocuments;
            @endphp
        @else
            @php
                $documents = new \App\Models\OldProjects\IAH\ProjectIAHDocuments();
            @endphp
        @endif

        <div class="info-grid">
            <!-- Aadhar Copy -->
            <div class="mb-3">
                <span class="info-label">Aadhar Copy:</span>
                @if($documents && $documents->aadhar_copy)
                    <span class="info-value">
                        <a href="{{ Storage::url($documents->aadhar_copy) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                        <a href="{{ Storage::url($documents->aadhar_copy) }}" download class="btn btn-sm btn-secondary">Download</a>
                    </span>
                @else
                    <span class="info-value text-muted">No file uploaded</span>
                @endif
            </div>

            <!-- Request Letter -->
            <div class="mb-3">
                <span class="info-label">Request Letter:</span>
                @if($documents && $documents->request_letter)
                    <span class="info-value">
                        <a href="{{ Storage::url($documents->request_letter) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                        <a href="{{ Storage::url($documents->request_letter) }}" download class="btn btn-sm btn-secondary">Download</a>
                    </span>
                @else
                    <span class="info-value text-muted">No file uploaded</span>
                @endif
            </div>

            <!-- Medical Reports -->
            <div class="mb-3">
                <span class="info-label">Medical Reports (Diagnosis):</span>
                @if($documents && $documents->medical_reports)
                    <span class="info-value">
                        <a href="{{ Storage::url($documents->medical_reports) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                        <a href="{{ Storage::url($documents->medical_reports) }}" download class="btn btn-sm btn-secondary">Download</a>
                    </span>
                @else
                    <span class="info-value text-muted">No file uploaded</span>
                @endif
            </div>

            <!-- Other Supporting Documents -->
            <div class="mb-3">
                <span class="info-label">Other Supporting Documents:</span>
                @if($documents && $documents->other_docs)
                    <span class="info-value">
                        <a href="{{ Storage::url($documents->other_docs) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                        <a href="{{ Storage::url($documents->other_docs) }}" download class="btn btn-sm btn-secondary">Download</a>
                    </span>
                @else
                    <span class="info-value text-muted">No file uploaded</span>
                @endif
            </div>
        </div>
    </div>
</div>
