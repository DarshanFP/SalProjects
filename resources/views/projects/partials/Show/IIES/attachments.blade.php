{{-- resources/views/projects/partials/show/IIES/attachments.blade.php --}}
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

<div class="card mb-3">
    <div class="card-header">
        <h5>Attachments</h5>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach ($fields as $field => $label)
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">{{ $label }}:</label>
                    @if(!empty($IIESAttachments))
                        @php
                            $files = $IIESAttachments->getFilesForField($field);
                        @endphp
                        @if($files && $files->count() > 0)
                            <div class="file-list">
                                @foreach($files as $file)
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
                                                    <br><small class="text-muted">Serial: {{ $file->serial_number }}</small>
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
                        @else
                            <p class="text-muted">No files uploaded.</p>
                        @endif
                    @else
                        <p class="text-muted">No files uploaded.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .file-item {
        background-color: #2d3748;
        border-color: #4a5568 !important;
        color: #e2e8f0;
    }
    .file-item .text-muted {
        color: #a0aec0 !important;
    }
    .file-list {
        margin-top: 10px;
    }
</style>
