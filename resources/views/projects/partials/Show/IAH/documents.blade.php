{{-- resources/views/projects/partials/show/IAH/documents.blade.php --}}
@php
    use App\Models\OldProjects\IAH\ProjectIAHDocuments;
    use App\Models\OldProjects\IAH\ProjectIAHDocumentFile;
    use Illuminate\Support\Facades\Storage;

    if (!isset($IAHDocuments) || empty($IAHDocuments)) {
        if (isset($project->project_id) && !empty($project->project_id)) {
            $IAHDocuments = ProjectIAHDocuments::where('project_id', $project->project_id)->first();
        } else {
            $IAHDocuments = null;
        }
    }

    $fields = [
        'aadhar_copy' => 'Aadhar Copy',
        'request_letter' => 'Request Letter',
        'medical_reports' => 'Medical Reports',
        'other_docs' => 'Other Documents'
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
                    @if(!empty($IAHDocuments))
                        @php
                            $files = $IAHDocuments->getFilesForField($field);
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
                                                    <a href="{{ route('projects.iah.documents.view', $file->id) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="{{ route('projects.iah.documents.download', $file->id) }}" class="btn btn-sm btn-secondary">
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
