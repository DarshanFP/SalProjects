<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Attach the following documents of the beneficiary:</h4>
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

                    @if($IAHDocuments && isset($IAHDocuments->$field) && $IAHDocuments->$field)
                        <p>Currently Attached:</p>
                        <a href="{{ Storage::url($IAHDocuments->$field) }}" target="_blank">
                            {{ basename($IAHDocuments->$field) }}
                        </a>
                        <br>
                        <a href="{{ Storage::url($IAHDocuments->$field) }}" download class="btn btn-success btn-sm">
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

    .btn {
        display: inline-block;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        text-decoration: none;
        border-radius: 4px;
    }

    .btn-success {
        background-color: #28a745;
        color: white;
        border: none;
        cursor: pointer;
    }

    .btn-success:hover {
        background-color: #218838;
    }

    .text-warning {
        color: #856404;
        font-size: 12px;
        font-style: italic;
    }
</style>
