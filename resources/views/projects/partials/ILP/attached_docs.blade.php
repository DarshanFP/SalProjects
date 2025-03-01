{{-- <div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Please attach the following documents of the beneficiary:</h4>
    </div>
    <div class="card-body">

        <!-- Aadhar Document -->
        <div class="mb-3">
            <label for="aadhar_doc" class="form-label">Self-attested Aadhar:</label>
            <input type="file" name="aadhar_doc" class="form-control" style="background-color: #202ba3;">
        </div>

        <!-- Request Letter -->
        <div class="mb-3">
            <label for="request_letter_doc" class="form-label">Request Letter:</label>
            <input type="file" name="request_letter_doc" class="form-control" style="background-color: #202ba3;">
        </div>

        <!-- Purchase Quotation -->
        <div class="mb-3">
            <label for="purchase_quotation_doc" class="form-label">Quotations regarding purchase:</label>
            <input type="file" name="purchase_quotation_doc" class="form-control" style="background-color: #202ba3;">
        </div>

        <!-- Other Documents -->
        <div class="mb-3">
            <label for="other_doc" class="form-label">Other relevant documents:</label>
            <input type="file" name="other_doc" class="form-control" style="background-color: #202ba3;">
        </div>

    </div>
</div> --}}

{{-- resources/views/projects/partials/ILP/attached_docs.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Please attach the following documents of the beneficiary:</h4>
    </div>
    <div class="card-body">
        @php
            $fields = [
                'aadhar_doc' => 'Self-attested Aadhar',
                'request_letter_doc' => 'Request Letter',
                'purchase_quotation_doc' => 'Quotations regarding purchase',
                'other_doc' => 'Other relevant documents',
            ];
        @endphp

        @foreach ($fields as $field => $label)
            <div class="mb-3">
                <label for="{{ $field }}" class="form-label">{{ $label }}:</label>
                <input type="file" name="attachments[{{ $field }}]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="background-color: #202ba3;">
            </div>
        @endforeach
    </div>
</div>
