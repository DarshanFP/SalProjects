{{-- resources/views/projects/partials/show/IIES/attachments.blade.php --}}
{{-- <pre>{{ print_r($IIESAttachments, true) }}</pre> --}}

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Attached Documents of the Beneficiary (IIES)</h4>
    </div>
    <div class="card-body">

        <!-- Aadhar Document -->
        <div class="mb-3">
            <span class="info-label">Aadhar Card (true copy):</span>
            @if(!empty($IIESAttachments['iies_aadhar_card']))
                <span class="info-value">
                    <a href="{{ Storage::url($IIESAttachments['iies_aadhar_card']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ Storage::url($IIESAttachments['iies_aadhar_card']) }}" download class="btn btn-sm btn-secondary">Download</a>
                </span>
            @else
                <span class="info-value text-muted">No file uploaded</span>
            @endif
        </div>

        <!-- Fee Quotation -->
        <div class="mb-3">
            <span class="info-label">Fee Quotation from Educational Institution (original):</span>
            @if(!empty($IIESAttachments['iies_fee_quotation']))
                <span class="info-value">
                    <a href="{{ Storage::url($IIESAttachments['iies_fee_quotation']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ Storage::url($IIESAttachments['iies_fee_quotation']) }}" download class="btn btn-sm btn-secondary">Download</a>
                </span>
            @else
                <span class="info-value text-muted">No file uploaded</span>
            @endif
        </div>

        <!-- Scholarship Proof -->
        <div class="mb-3">
            <span class="info-label">Proof of Scholarship Received in Previous Year:</span>
            @if(!empty($IIESAttachments['iies_scholarship_proof']))
                <span class="info-value">
                    <a href="{{ Storage::url($IIESAttachments['iies_scholarship_proof']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ Storage::url($IIESAttachments['iies_scholarship_proof']) }}" download class="btn btn-sm btn-secondary">Download</a>
                </span>
            @else
                <span class="info-value text-muted">No file uploaded</span>
            @endif
        </div>

        <!-- Medical Confirmation -->
        <div class="mb-3">
            <span class="info-label">Medical Confirmation (Parent ill-health):</span>
            @if(!empty($IIESAttachments['iies_medical_confirmation']))
                <span class="info-value">
                    <a href="{{ Storage::url($IIESAttachments['iies_medical_confirmation']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ Storage::url($IIESAttachments['iies_medical_confirmation']) }}" download class="btn btn-sm btn-secondary">Download</a>
                </span>
            @else
                <span class="info-value text-muted">No file uploaded</span>
            @endif
        </div>

        <!-- Caste Certificate -->
        <div class="mb-3">
            <span class="info-label">Caste Certificate (true copy):</span>
            @if(!empty($IIESAttachments['iies_caste_certificate']))
                <span class="info-value">
                    <a href="{{ Storage::url($IIESAttachments['iies_caste_certificate']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ Storage::url($IIESAttachments['iies_caste_certificate']) }}" download class="btn btn-sm btn-secondary">Download</a>
                </span>
            @else
                <span class="info-value text-muted">No file uploaded</span>
            @endif
        </div>

        <!-- Self Declaration -->
        <div class="mb-3">
            <span class="info-label">Self Declaration (single parent):</span>
            @if(!empty($IIESAttachments['iies_self_declaration']))
                <span class="info-value">
                    <a href="{{ Storage::url($IIESAttachments['iies_self_declaration']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ Storage::url($IIESAttachments['iies_self_declaration']) }}" download class="btn btn-sm btn-secondary">Download</a>
                </span>
            @else
                <span class="info-value text-muted">No file uploaded</span>
            @endif
        </div>

        <!-- Death Certificate -->
        <div class="mb-3">
            <span class="info-label">Death Certificate (true copy):</span>
            @if(!empty($IIESAttachments['iies_death_certificate']))
                <span class="info-value">
                    <a href="{{ Storage::url($IIESAttachments['iies_death_certificate']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ Storage::url($IIESAttachments['iies_death_certificate']) }}" download class="btn btn-sm btn-secondary">Download</a>
                </span>
            @else
                <span class="info-value text-muted">No file uploaded</span>
            @endif
        </div>

        <!-- Request Letter -->
        <div class="mb-3">
            <span class="info-label">Request Letter (original copy):</span>
            @if(!empty($IIESAttachments['iies_request_letter']))
                <span class="info-value">
                    <a href="{{ Storage::url($IIESAttachments['iies_request_letter']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                    <a href="{{ Storage::url($IIESAttachments['iies_request_letter']) }}" download class="btn btn-sm btn-secondary">Download</a>
                </span>
            @else
                <span class="info-value text-muted">No file uploaded</span>
            @endif
        </div>

    </div>
</div>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
