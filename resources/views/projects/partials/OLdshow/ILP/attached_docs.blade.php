{{-- resources/views/projects/partials/Edit/ILP/attached_docs.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Attach the following documents of the beneficiary:</h4>
    </div>
    <div class="card-body">

        <!-- Aadhar Document -->
        <div class="mb-3">
            <label for="aadhar_doc" class="form-label">Self-attested Aadhar:</label>
            <input type="file" name="aadhar_doc" class="form-control">
            @if($documents->aadhar_doc)
                <p>Currently Attached: <a href="{{ asset('storage/' . $documents->aadhar_doc) }}" target="_blank">View Document</a></p>
            @endif
        </div>

        <!-- Request Letter -->
        <div class="mb-3">
            <label for="request_letter_doc" class="form-label">Request Letter:</label>
            <input type="file" name="request_letter_doc" class="form-control">
            @if($documents->request_letter_doc)
                <p>Currently Attached: <a href="{{ asset('storage/' . $documents->request_letter_doc) }}" target="_blank">View Document</a></p>
            @endif
        </div>

        <!-- Purchase Quotation -->
        <div class="mb-3">
            <label for="purchase_quotation_doc" class="form-label">Quotations regarding purchase:</label>
            <input type="file" name="purchase_quotation_doc" class="form-control">
            @if($documents->purchase_quotation_doc)
                <p>Currently Attached: <a href="{{ asset('storage/' . $documents->purchase_quotation_doc) }}" target="_blank">View Document</a></p>
            @endif
        </div>

        <!-- Other Documents -->
        <div class="mb-3">
            <label for="other_doc" class="form-label">Other relevant documents:</label>
            <input type="file" name="other_doc" class="form-control">
            @if($documents->other_doc)
                <p>Currently Attached: <a href="{{ asset('storage/' . $documents->other_doc) }}" target="_blank">View Document</a></p>
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
