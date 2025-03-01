{{-- resources/views/projects/partials/Edit/IIES/attachments.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Please Attach the Following Documents IIES</h4>
    </div>
    <div class="card-body">
        <div class="row">
            @php
                $fields = [
                    'iies_aadhar_card' => 'Aadhar Card (true copy)',
                    'iies_fee_quotation' => 'Fee Quotation from Educational Institution (original)',
                    'iies_scholarship_proof' => 'Proof of Scholarship Received Previous Year',
                    'iies_medical_confirmation' => 'Medical Confirmation (ill health of parents - original)',
                    'iies_caste_certificate' => 'Caste Certificate (true copy)',
                    'iies_self_declaration' => 'Self Declaration (single parent - original)',
                    'iies_death_certificate' => 'Death Certificate (deceased parents - true copy)',
                    'iies_request_letter' => 'Request Letter (original copy)'
                ];
            @endphp

            @foreach ($fields as $field => $label)
                <div class="col-md-6 form-group">
                    <label>{{ $label }}</label>
                    <input type="file" name="{{ $field }}" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">

                    @if($IIESAttachments && isset($IIESAttachments->$field) && $IIESAttachments->$field)
                    <p>Currently Attached:</p>
                    <a href="{{ Storage::url($IIESAttachments->$field) }}" target="_blank">
                        {{ basename($IIESAttachments->$field) }}
                    </a>
                    <br>
                    <a href="{{ Storage::url($IIESAttachments->$field) }}" download class="btn btn-success btn-sm">
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


<!-- Styles -->
<style>
    .form-group {
        margin-bottom: 20px;
    }

    .form-control-file {
        max-width: 100%;
        background-color: #202ba3;
        color: white;
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
