{{-- resources/views/projects/partials/IIES/attachments.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Please Attach the Following Documents IIES</h4>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- <input type="hidden" name="iies_project_id" value="{{ $projectId }}"> --}}
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
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .form-group {
        margin-bottom: 20px; /* Add spacing between fields */
    }

    .form-control-file {
        max-width: 100%; /* Ensure full width in each column */
        background-color: #202ba3;
        color: white;
    }

    label {
        margin-bottom: 5px; /* Add space between label and input */
    }

    .card-body {
        padding: 20px;
    }

    .row {
        margin: 0; /* Ensure the row uses the full width */
    }

    .col-md-6 {
        padding: 10px; /* Add spacing between columns */
    }
</style>
