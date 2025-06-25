{{-- resources/views/projects/partials/Show/ILP/attached_docs.blade.php --}}
{{-- <pre>{{ print_r($ILPAttachedDocuments, true) }}</pre> --}}

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Attached Documents of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @if($ILPAttachedDocuments)
            @php
                $documents = $ILPAttachedDocuments;
            @endphp
        @else
            @php
                $documents = [];
            @endphp
        @endif

        <div class="info-grid">
            <!-- Aadhar Document -->
            <div class="mb-3">
                <span class="info-label">Self-attested Aadhar:</span>
                @if(!empty($documents['aadhar_doc']))
                    <span class="info-value">
                        <a href="{{ Storage::url($documents['aadhar_doc']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                        <a href="{{ Storage::url($documents['aadhar_doc']) }}" download class="btn btn-sm btn-secondary">Download</a>
                    </span>
                @else
                    <span class="info-value text-muted">No file uploaded</span>
                @endif
            </div>

            <!-- Request Letter -->
            <div class="mb-3">
                <span class="info-label">Request Letter:</span>
                @if(!empty($documents['request_letter_doc']))
                    <span class="info-value">
                        <a href="{{ Storage::url($documents['request_letter_doc']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                        <a href="{{ Storage::url($documents['request_letter_doc']) }}" download class="btn btn-sm btn-secondary">Download</a>
                    </span>
                @else
                    <span class="info-value text-muted">No file uploaded</span>
                @endif
            </div>

            <!-- Purchase Quotation -->
            <div class="mb-3">
                <span class="info-label">Quotations regarding purchase:</span>
                @if(!empty($documents['purchase_quotation_doc']))
                    <span class="info-value">
                        <a href="{{ Storage::url($documents['purchase_quotation_doc']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                        <a href="{{ Storage::url($documents['purchase_quotation_doc']) }}" download class="btn btn-sm btn-secondary">Download</a>
                    </span>
                @else
                    <span class="info-value text-muted">No file uploaded</span>
                @endif
            </div>

            <!-- Other Documents -->
            <div class="mb-3">
                <span class="info-label">Other relevant documents:</span>
                @if(!empty($documents['other_doc']))
                    <span class="info-value">
                        <a href="{{ Storage::url($documents['other_doc']) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                        <a href="{{ Storage::url($documents['other_doc']) }}" download class="btn btn-sm btn-secondary">Download</a>
                    </span>
                @else
                    <span class="info-value text-muted">No file uploaded</span>
                @endif
            </div>
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
