{{-- resources/views/projects/partials/show/IES/attachments.blade.php --}}
@php
    use App\Models\OldProjects\IES\ProjectIESAttachments;
    use App\Models\OldProjects\IES\ProjectIESAttachmentFile;
    use Illuminate\Support\Facades\Storage;

    if (!isset($IESAttachments) || empty($IESAttachments)) {
        if (isset($project->project_id) && !empty($project->project_id)) {
            $IESAttachments = ProjectIESAttachments::where('project_id', $project->project_id)->first();
        } else {
            $IESAttachments = null;
        }
    }

    $fields = [
        'aadhar_card' => 'Self-attested Aadhar',
        'fee_quotation' => 'Fee Quotation from Institution',
        'scholarship_proof' => 'Proof of Scholarship',
        'medical_confirmation' => 'Medical Confirmation',
        'caste_certificate' => 'Caste Certificate',
        'self_declaration' => 'Self Declaration',
        'death_certificate' => 'Death Certificate',
        'request_letter' => 'Request Letter'
    ];
@endphp

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Attached Documents:</h4>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach ($fields as $field => $label)
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">{{ $label }}:</label>
                    @if(!empty($IESAttachments))
                        @php
                            $files = $IESAttachments->getFilesForField($field);
                        @endphp
                        @if($files && $files instanceof \Illuminate\Support\Collection && $files->count() > 0)
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
                                                    @if(isset($file->id))
                                                        <a href="{{ route('projects.ies.attachments.view', $file->id) }}" target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <a href="{{ route('projects.ies.attachments.download', $file->id) }}" class="btn btn-sm btn-secondary">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    @else
                                                        <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <a href="{{ Storage::url($file->file_path) }}" download class="btn btn-sm btn-secondary">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    @endif
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
