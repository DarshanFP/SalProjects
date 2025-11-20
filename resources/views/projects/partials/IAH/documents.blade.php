{{-- resources/views/projects/partials/IAH/documents.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Please attach the following documents of the beneficiary:</h4>
    </div>
    <div class="card-body">
        <div class="row">
            @php
                $fields = [
                    'aadhar_copy'     => 'Aadhar Copy',
                    'request_letter'  => 'Request Letter',
                    'medical_reports' => 'Medical Reports (Diagnosis)',
                    'other_docs'      => 'Any Other Supporting Documents',
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
