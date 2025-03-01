{{-- resources/views/projects/partials/show/IIES/attachments.blade.php --}}
@php
    $fields = [
        'iies_aadhar_card'          => 'Aadhar Card (true copy)',
        'iies_fee_quotation'        => 'Fee Quotation from Educational Institution (original)',
        'iies_scholarship_proof'    => 'Proof of Scholarship Received Previous Year',
        'iies_medical_confirmation' => 'Medical Confirmation (ill health of parents - original)',
        'iies_caste_certificate'    => 'Caste Certificate (true copy)',
        'iies_self_declaration'     => 'Self Declaration (single parent - original)',
        'iies_death_certificate'    => 'Death Certificate (deceased parents - true copy)',
        'iies_request_letter'       => 'Request Letter (original copy)',
    ];
@endphp

<div class="mb-3 card">
    <div class="card-header">
        <h4>IIES Attachments - (View Only)</h4>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach ($fields as $field => $label)
                <div class="col-md-6 form-group">
                    <label>{{ $label }}</label>
                    @if ($IIESAttachments && $IIESAttachments->$field)
                        <p>File Attached:
                            <a href="{{ Storage::url($IIESAttachments->$field) }}" target="_blank">
                                {{ basename($IIESAttachments->$field) }}
                            </a>
                        </p>
                        <a href="{{ Storage::url($IIESAttachments->$field) }}"
                           download
                           class="btn btn-success btn-sm">
                           Download
                        </a>
                    @else
                        <p class="text-warning">No file attached.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .form-group {
        margin-bottom: 20px;
    }
    label {
        margin-bottom: 5px;
    }
    .card-body {
        padding: 20px;
    }
    .row {
        margin: 0;
    }
    .col-md-6 {
        padding: 10px;
    }
</style>
