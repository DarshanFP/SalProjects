{{-- resources/views/projects/partials/IIES/attachments.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Please Attach the Following Documents IIES</h4>
        <small class="text-muted">You can upload multiple files for each field. Maximum 5 MB per file.</small>
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
                <div class="col-md-6 form-group mb-4">
                    <label class="form-label">{{ $label }}</label>
                    <div class="file-upload-container" data-field="{{ $field }}">
                        <div class="file-input-wrapper mb-2">
                            <input type="file"
                                   name="{{ $field }}[]"
                                   class="form-control-file file-input"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   onchange="validateIESFile(this, '{{ $field }}')"
                                   data-field="{{ $field }}">
                            <input type="text"
                                   name="{{ $field }}_names[]"
                                   class="form-control mt-2"
                                   placeholder="Optional: Custom file name (without extension)"
                                   data-field="{{ $field }}">
                            <textarea name="{{ $field }}_descriptions[]"
                                      class="form-control mt-2"
                                      rows="2"
                                      placeholder="Optional: File description"
                                      data-field="{{ $field }}"></textarea>
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-success add-file-btn"
                                data-field="{{ $field }}">
                            <i class="fas fa-plus"></i> Add Another File
                        </button>
                    </div>
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
    }

    .file-upload-container {
        border: 1px solid #4a5568;
        padding: 15px;
        border-radius: 5px;
        background-color: #2d3748;
        color: #e2e8f0;
    }

    .file-input-wrapper {
        margin-bottom: 10px;
    }

    .file-input-wrapper:not(:first-child) {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed #4a5568;
    }

    .file-upload-container .form-control {
        background-color: #1a202c;
        border-color: #4a5568;
        color: #e2e8f0;
    }

    .file-upload-container .form-control:focus {
        background-color: #1a202c;
        border-color: #667eea;
        color: #e2e8f0;
    }

    .file-upload-container .form-control::placeholder {
        color: #718096;
    }

    .file-upload-container .form-control-file {
        color: #e2e8f0;
    }

    .file-upload-container label {
        color: #e2e8f0;
    }

    .remove-file-btn {
        margin-top: 5px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add file input functionality
    document.querySelectorAll('.add-file-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const field = this.getAttribute('data-field');
            const container = this.closest('.file-upload-container');
            const firstWrapper = container.querySelector('.file-input-wrapper');

            // Clone the first file input wrapper
            const newWrapper = firstWrapper.cloneNode(true);

            // Clear file input value
            newWrapper.querySelector('input[type="file"]').value = '';
            newWrapper.querySelector('input[type="text"]').value = '';
            newWrapper.querySelector('textarea').value = '';

            // Add remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-danger remove-file-btn';
            removeBtn.innerHTML = '<i class="fas fa-times"></i> Remove';
            removeBtn.onclick = function() {
                newWrapper.remove();
            };
            newWrapper.appendChild(removeBtn);

            // Insert before the add button
            container.insertBefore(newWrapper, this);
        });
    });
});
</script>

<script src="{{ asset('js/attachments-validation.js') }}"></script>
