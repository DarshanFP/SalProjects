{{-- resources/views/projects/partials/Edit/ILP/attached_docs.blade.php --}}
{{-- <pre>{{ print_r($ILPAttachedDocuments, true) }}</pre> --}}

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Attach the following documents of the beneficiary:</h4>
    </div>
    <div class="card-body">

        <!-- Aadhar Document -->
        <div class="mb-3">
            <label for="aadhar_doc" class="form-label">Self-attested Aadhar:</label>
            <input type="file" name="aadhar_doc" class="form-control">
            @if(!empty($ILPAttachedDocuments['aadhar_doc']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $ILPAttachedDocuments['aadhar_doc']) }}" target="_blank">View Document</a></p>
            @endif
        </div>

        <!-- Request Letter -->
        <div class="mb-3">
            <label for="request_letter_doc" class="form-label">Request Letter:</label>
            <input type="file" name="request_letter_doc" class="form-control">
            @if(!empty($ILPAttachedDocuments['request_letter_doc']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $ILPAttachedDocuments['request_letter_doc']) }}" target="_blank">View Document</a></p>
            @endif
        </div>

        <!-- Purchase Quotation -->
        <div class="mb-3">
            <label for="purchase_quotation_doc" class="form-label">Quotations regarding purchase:</label>
            <input type="file" name="purchase_quotation_doc" class="form-control">
            @if(!empty($ILPAttachedDocuments['purchase_quotation_doc']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $ILPAttachedDocuments['purchase_quotation_doc']) }}" target="_blank">View Document</a></p>
            @endif
        </div>

        <!-- Other Documents -->
        <div class="mb-3">
            <label for="other_doc" class="form-label">Other relevant documents:</label>
            <input type="file" name="other_doc" class="form-control">
            @if(!empty($ILPAttachedDocuments['other_doc']))
                <p>Currently Attached: <a href="{{ asset('storage/' . $ILPAttachedDocuments['other_doc']) }}" target="_blank">View Document</a></p>
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
