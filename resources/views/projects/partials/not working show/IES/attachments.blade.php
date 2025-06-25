{{-- resources/views/projects/partials/show/IES/attachments.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Attached Documents:</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- LEFT COLUMN -->
            <div class="col-md-6">
                @foreach ([
                    'aadhar_card' => 'Self-attested Aadhar',
                    'fee_quotation' => 'Fee Quotation from Institution',
                    'scholarship_proof' => 'Proof of Scholarship',
                    'medical_confirmation' => 'Medical Confirmation'
                ] as $name => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}:</label>
                        @if(!empty($IESAttachments->$name))
                            <p>Attached:</p>
                            <a href="{{ Storage::url($IESAttachments->$name) }}" target="_blank">
                                {{ basename($IESAttachments->$name) }}
                            </a>
                            <br>
                            <a href="{{ Storage::url($IESAttachments->$name) }}" download class="btn btn-green">
                                Download
                            </a>
                        @else
                            <p class="text-muted">No file uploaded.</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-md-6">
                @foreach ([
                    'caste_certificate' => 'Caste Certificate',
                    'self_declaration' => 'Self Declaration',
                    'death_certificate' => 'Death Certificate',
                    'request_letter' => 'Request Letter'
                ] as $name => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}:</label>
                        @if(!empty($IESAttachments->$name))
                            <p>Attached:</p>
                            <a href="{{ Storage::url($IESAttachments->$name) }}" target="_blank">
                                {{ basename($IESAttachments->$name) }}
                            </a>
                            <br>
                            <a href="{{ Storage::url($IESAttachments->$name) }}" download class="btn btn-green">
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
</div>

<!-- Styles -->
<style>
/* Styling for document display */
.btn-green {
    background-color: #28a745;
    color: white;
    border: none;
    cursor: pointer;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    border-radius: 4px;
}

.btn-green:hover {
    background-color: #218838;
}

.text-muted {
    color: #6c757d;
}
</style>
