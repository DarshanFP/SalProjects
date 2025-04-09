{{-- resources/views/projects/partials/show/IIES/attachments.blade.php --}}
{{-- <pre>{{ print_r($IIESAttachments, true) }}</pre> --}}

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">View: Attach the following documents of the beneficiary (IIES)</h4>
    </div>
    <div class="card-body">

        <!-- Aadhar Document -->
        <div class="mb-3">
            <label for="iies_aadhar_card" class="form-label">Aadhar Card (true copy):</label>
            @if(!empty($IIESAttachments['iies_aadhar_card']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $IIESAttachments['iies_aadhar_card']) }}" target="_blank">View Document</a></p>
            @else
                <p class="text-muted">No file uploaded.</p>
            @endif
        </div>

        <!-- Fee Quotation -->
        <div class="mb-3">
            <label for="iies_fee_quotation" class="form-label">Fee Quotation from Educational Institution (original):</label>
            @if(!empty($IIESAttachments['iies_fee_quotation']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $IIESAttachments['iies_fee_quotation']) }}" target="_blank">View Document</a></p>
            @else
                <p class="text-muted">No file uploaded.</p>
            @endif
        </div>

        <!-- Scholarship Proof -->
        <div class="mb-3">
            <label for="iies_scholarship_proof" class="form-label">Proof of Scholarship Received in Previous Year:</label>
            @if(!empty($IIESAttachments['iies_scholarship_proof']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $IIESAttachments['iies_scholarship_proof']) }}" target="_blank">View Document</a></p>
            @else
                <p class="text-muted">No file uploaded.</p>
            @endif
        </div>

        <!-- Medical Confirmation -->
        <div class="mb-3">
            <label for="iies_medical_confirmation" class="form-label">Medical Confirmation (Parent ill-health):</label>
            @if(!empty($IIESAttachments['iies_medical_confirmation']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $IIESAttachments['iies_medical_confirmation']) }}" target="_blank">View Document</a></p>
            @else
                <p class="text-muted">No file uploaded.</p>
            @endif
        </div>

        <!-- Caste Certificate -->
        <div class="mb-3">
            <label for="iies_caste_certificate" class="form-label">Caste Certificate (true copy):</label>
            @if(!empty($IIESAttachments['iies_caste_certificate']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $IIESAttachments['iies_caste_certificate']) }}" target="_blank">View Document</a></p>
            @else
                <p class="text-muted">No file uploaded.</p>
            @endif
        </div>

        <!-- Self Declaration -->
        <div class="mb-3">
            <label for="iies_self_declaration" class="form-label">Self Declaration (single parent):</label>
            @if(!empty($IIESAttachments['iies_self_declaration']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $IIESAttachments['iies_self_declaration']) }}" target="_blank">View Document</a></p>
            @else
                <p class="text-muted">No file uploaded.</p>
            @endif
        </div>

        <!-- Death Certificate -->
        <div class="mb-3">
            <label for="iies_death_certificate" class="form-label">Death Certificate (true copy):</label>
            @if(!empty($IIESAttachments['iies_death_certificate']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $IIESAttachments['iies_death_certificate']) }}" target="_blank">View Document</a></p>
            @else
                <p class="text-muted">No file uploaded.</p>
            @endif
        </div>

        <!-- Request Letter -->
        <div class="mb-3">
            <label for="iies_request_letter" class="form-label">Request Letter (original copy):</label>
            @if(!empty($IIESAttachments['iies_request_letter']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $IIESAttachments['iies_request_letter']) }}" target="_blank">View Document</a></p>
            @else
                <p class="text-muted">No file uploaded.</p>
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
