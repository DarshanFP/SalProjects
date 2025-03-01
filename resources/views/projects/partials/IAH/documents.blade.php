{{-- resources/views/projects/partials/IAH/documents.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Please attach the following documents of the beneficiary:</h4>
    </div>
    <div class="card-body">
        @php
            // These fields must match your model's columns exactly
            $fields = [
                'aadhar_copy'     => 'Aadhar Copy',
                'request_letter'  => 'Request Letter',
                'medical_reports' => 'Medical Reports (Diagnosis)',
                'other_docs'      => 'Any Other Supporting Documents',
            ];
        @endphp

        @foreach ($fields as $field => $label)
            <div class="mb-3">
                <label for="{{ $field }}" class="form-label">{{ $label }}:</label>
                <input
                    type="file"
                    name="attachments[{{ $field }}]"
                    class="form-control"
                    accept=".pdf,.jpg,.jpeg,.png"
                    style="background-color: #202ba3;">
            </div>
        @endforeach
    </div>
</div>
