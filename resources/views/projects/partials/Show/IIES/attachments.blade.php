{{-- resources/views/projects/partials/show/IIES/attachments.blade.php --}}

@php
    // If IIESAttachments is empty, try to fetch it directly
    if (!isset($IIESAttachments) || empty($IIESAttachments)) {
        $controller = new \App\Http\Controllers\Projects\IIES\IIESAttachmentsController();
        $IIESAttachments = $controller->show($project->project_id ?? 'IIES-0013');
    }
@endphp

<div class="card mb-3">
    <div class="card-header">
        <h5>Attachments</h5>
    </div>
    <div class="card-body">

        <!-- Aadhar Document -->
        <div class="attachment-row">
            <span class="attachment-label">Aadhar Card (true copy):</span>
            <div class="attachment-actions">
                @if(!empty($IIESAttachments['iies_aadhar_card']))
                    <a href="{{ $IIESAttachments['iies_aadhar_card'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IIESAttachments['iies_aadhar_card'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Fee Quotation -->
        <div class="attachment-row">
            <span class="attachment-label">Fee Quotation from Educational Institution (original):</span>
            <div class="attachment-actions">
                @if(!empty($IIESAttachments['iies_fee_quotation']))
                    <a href="{{ $IIESAttachments['iies_fee_quotation'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IIESAttachments['iies_fee_quotation'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Scholarship Proof -->
        <div class="attachment-row">
            <span class="attachment-label">Proof of Scholarship Received in Previous Year:</span>
            <div class="attachment-actions">
                @if(!empty($IIESAttachments['iies_scholarship_proof']))
                    <a href="{{ $IIESAttachments['iies_scholarship_proof'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IIESAttachments['iies_scholarship_proof'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Medical Confirmation -->
        <div class="attachment-row">
            <span class="attachment-label">Medical Confirmation (Parent ill-health):</span>
            <div class="attachment-actions">
                @if(!empty($IIESAttachments['iies_medical_confirmation']))
                    <a href="{{ $IIESAttachments['iies_medical_confirmation'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IIESAttachments['iies_medical_confirmation'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Caste Certificate -->
        <div class="attachment-row">
            <span class="attachment-label">Caste Certificate (true copy):</span>
            <div class="attachment-actions">
                @if(!empty($IIESAttachments['iies_caste_certificate']))
                    <a href="{{ $IIESAttachments['iies_caste_certificate'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IIESAttachments['iies_caste_certificate'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Self Declaration -->
        <div class="attachment-row">
            <span class="attachment-label">Self Declaration (single parent):</span>
            <div class="attachment-actions">
                @if(!empty($IIESAttachments['iies_self_declaration']))
                    <a href="{{ $IIESAttachments['iies_self_declaration'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IIESAttachments['iies_self_declaration'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Death Certificate -->
        <div class="attachment-row">
            <span class="attachment-label">Death Certificate (true copy):</span>
            <div class="attachment-actions">
                @if(!empty($IIESAttachments['iies_death_certificate']))
                    <a href="{{ $IIESAttachments['iies_death_certificate'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IIESAttachments['iies_death_certificate'] }}" download class="btn btn-sm btn-secondary">Download</a>
                @else
                    <span class="text-muted">No file uploaded</span>
                @endif
            </div>
        </div>

        <!-- Request Letter -->
        <div class="attachment-row">
            <span class="attachment-label">Request Letter (original copy):</span>
            <div class="attachment-actions">
                @if(!empty($IIESAttachments['iies_request_letter']))
                    <a href="{{ $IIESAttachments['iies_request_letter'] }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ $IIESAttachments['iies_request_letter'] }}" download class="btn btn-sm btn-secondary">Download</a>
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
    }

    .attachment-label {
        font-weight: bold;
        flex: 1;
        margin-right: 20px;
    }

    .attachment-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        min-width: 200px;
        justify-content: flex-end;
    }

    .attachment-actions .btn {
        white-space: nowrap;
    }

    .attachment-actions .text-muted {
        font-style: italic;
        color: #6c757d;
    }

    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
