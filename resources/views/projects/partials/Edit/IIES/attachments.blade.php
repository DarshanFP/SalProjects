{{-- resources/views/projects/partials/Edit/IIES/attachments.blade.php --}}
@php
    use App\Models\OldProjects\IIES\ProjectIIESAttachments;
    use App\Models\OldProjects\IIES\ProjectIIESAttachmentFile;
    use Illuminate\Support\Facades\Storage;

    if (!isset($IIESAttachments) || empty($IIESAttachments)) {
        if (isset($project->project_id) && !empty($project->project_id)) {
            $IIESAttachments = ProjectIIESAttachments::where('project_id', $project->project_id)->first();
        } else {
            $IIESAttachments = null;
        }
    }

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

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Attach the following documents IIES</h4>
        <small class="text-muted">You can upload multiple files for each field. Maximum 5 MB per file.</small>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach ($fields as $field => $label)
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">{{ $label }}:</label>

                    {{-- Show existing files --}}
                    @if(!empty($IIESAttachments))
                        @php
                            $existingFiles = $IIESAttachments->getFilesForField($field);
                        @endphp
                        @if($existingFiles && $existingFiles->count() > 0)
                            <div class="existing-files mb-3">
                                <strong>Existing Files:</strong>
                                @foreach($existingFiles as $file)
                                    @php
                                        $fileExists = Storage::disk('public')->exists($file->file_path);
                                    @endphp
                                    <div class="file-item mb-2 p-2 border rounded">
                                        @if($fileExists)
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="{{ \App\Helpers\AttachmentFileNamingHelper::getFileIcon($file->file_path) ?? config('attachments.file_icons.default') }}"></i>
                                                    <strong>{{ $file->file_name }}</strong>
                                                    @if($file->description)
                                                        <br><small class="text-muted">{{ $file->description }}</small>
                                                    @endif
                                                </div>
                                                <div>
                                                    <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="{{ Storage::url($file->file_path) }}" download class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> File not found: {{ $file->file_name }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif

                    {{-- New file upload section --}}
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

    .file-item {
        background-color: #2d3748;
        border-color: #4a5568 !important;
        color: #e2e8f0;
    }

    .file-item .text-muted {
        color: #a0aec0 !important;
    }

    .existing-files {
        margin-bottom: 15px;
        color: #e2e8f0;
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
