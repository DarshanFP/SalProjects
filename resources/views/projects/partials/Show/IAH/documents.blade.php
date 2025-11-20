{{-- resources/views/projects/partials/Show/IAH/documents.blade.php --}}

@php
    // If IAHDocuments is empty, try to fetch it directly
    if (!isset($IAHDocuments) || empty($IAHDocuments)) {
        $controller = new \App\Http\Controllers\Projects\IAH\IAHDocumentsController();
        $IAHDocuments = $controller->show($project->project_id ?? 'IAH-0013');
    }
@endphp

<div class="card mb-3">
    <div class="card-header">
        <h5>Attached Documents of the Beneficiary</h5>
    </div>
    <div class="card-body">

        <!-- Aadhar Copy -->
        <div class="attachment-row">
            <span class="attachment-label">Aadhar Copy:</span>
            <div class="attachment-actions">
                @if(!empty($IAHDocuments['aadhar_copy']))
                    <a href="{{ $IAHDocuments['aadhar_copy'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IAHDocuments['aadhar_copy'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Request Letter -->
        <div class="attachment-row">
            <span class="attachment-label">Request Letter:</span>
            <div class="attachment-actions">
                @if(!empty($IAHDocuments['request_letter']))
                    <a href="{{ $IAHDocuments['request_letter'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IAHDocuments['request_letter'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Medical Reports -->
        <div class="attachment-row">
            <span class="attachment-label">Medical Reports (Diagnosis):</span>
            <div class="attachment-actions">
                @if(!empty($IAHDocuments['medical_reports']))
                    <a href="{{ $IAHDocuments['medical_reports'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IAHDocuments['medical_reports'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Other Supporting Documents -->
        <div class="attachment-row">
            <span class="attachment-label">Other Supporting Documents:</span>
            <div class="attachment-actions">
                @if(!empty($IAHDocuments['other_docs']))
                    <a href="{{ $IAHDocuments['other_docs'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IAHDocuments['other_docs'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .attachment-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .attachment-row:last-child {
        border-bottom: none;
    }

    .attachment-label {
        font-weight: bold;
        color: #333;
        flex: 1;
    }

    .attachment-actions {
        flex: 1;
        text-align: right;
    }

    .btn {
        margin-left: 5px;
    }

    .text-muted {
        color: #6c757d;
        font-style: italic;
    }
</style>
