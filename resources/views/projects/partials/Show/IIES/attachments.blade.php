
<!-- resources/views/projects/partials/show/IIES/attachments.blade.php -->
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Attached Documents: IIES</h4>
    </div>
    <div class="card-body">
        @php
            $fields = [
                'iies_aadhar_card'          => 'Aadhar Card (true copy)',
                'iies_fee_quotation'        => 'Fee Quotation from Educational Institution (original)',
                'iies_scholarship_proof'    => 'Proof of Scholarship Received in Previous Year',
                'iies_medical_confirmation' => 'Medical Confirmation (Parent ill-health)',
                'iies_caste_certificate'    => 'Caste Certificate (true copy)',
                'iies_self_declaration'     => 'Self Declaration (single parent)',
                'iies_death_certificate'    => 'Death Certificate (true copy)',
                'iies_request_letter'       => 'Request Letter (original copy)'
            ];
        @endphp

        <div class="row">
            @foreach ($fields as $field => $label)
                <div class="mb-3 col-md-6">
                    <label class="form-label">{{ $label }}:</label><br/>
                    @if(!empty($IIESAttachments) && !empty($IIESAttachments->$field))
                        <p>Attached:</p>
                        <a href="{{ Storage::url($IIESAttachments->$field) }}" target="_blank">
                            {{ basename($IIESAttachments->$field) }}
                        </a>
                        <br>
                        <a href="{{ Storage::url($IIESAttachments->$field) }}" download class="btn btn-green">
                            Download
                        </a>
                    @else
                        <p class="text-muted">No file uploaded.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
