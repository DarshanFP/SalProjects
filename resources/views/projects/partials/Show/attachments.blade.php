{{-- resources/views/projects/partials/Show/attachments.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachments</h4>
    </div>
    <div class="card-body attachments-section ATT-attachments-container">
        @if($project->attachments->count() > 0)
            <div class="ATT-attachments-list">
                @foreach($project->attachments as $index => $attachment)
                    @php
                        $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path);
                        $fileExtension = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                        $fileIcon = config("attachments.file_icons.{$fileExtension}", config('attachments.file_icons.default'));
                    @endphp

                    <div class="ATT-attachment-item">
                        <div class="info-grid ATT-info-grid">
                            <div class="info-label ATT-info-label">
                                <strong>Attachment {{ $index + 1 }}:</strong>
                            </div>
                            <div class="info-value ATT-info-value">
                                @if($fileExists)
                                    @php
                                        $fileSize = \Illuminate\Support\Facades\Storage::disk('public')->size($attachment->file_path);
                                    @endphp
                                    <div class="ATT-file-wrapper">
                                        <div class="ATT-file-info-block">
                                            <span class="ATT-file-name-item">
                                                <i class="{{ $fileIcon }}"></i>
                                                <strong>{{ e($attachment->file_name) }}</strong>
                                            </span>
                                            <span class="ATT-file-size-item text-muted small">
                                                Size: {{ format_indian($fileSize / 1024, 2) }} KB
                                            </span>
                                            @if($attachment->description)
                                                <span class="ATT-file-description-item text-muted small">
                                                    <strong>Description:</strong> {{ e($attachment->description) }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="ATT-file-buttons">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ asset('storage/' . $attachment->file_path) }}"
                                                   target="_blank"
                                                   class="btn btn-outline-primary"
                                                   title="View file">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="{{ route('projects.attachments.download', $attachment->id) }}"
                                                   class="btn btn-outline-success"
                                                   title="Download file">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> File not found in storage: {{ e($attachment->file_name) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="info-grid ATT-info-grid">
                <div class="info-label ATT-info-label"><strong>Status:</strong></div>
                <div class="info-value ATT-info-value text-muted">No attachments uploaded yet.</div>
            </div>
        @endif
    </div>
</div>

<style>
/* Custom CSS for Attachments Show Partial - All classes prefixed with ATT */
.ATT-attachments-container {
    padding: 1rem;
}

.ATT-attachments-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.ATT-attachment-item {
    width: 100%;
}

.ATT-attachments-container .ATT-info-grid {
    display: grid;
    grid-template-columns: 20% 80%;
    grid-gap: 12px 15px;
    align-items: start;
    width: 100%;
}

.ATT-attachments-container .ATT-info-label {
    font-weight: bold;
    margin-right: 10px;
    padding-top: 2px;
    grid-column: 1;
}

.ATT-attachments-container .ATT-info-value {
    word-wrap: break-word;
    overflow-wrap: break-word;
    line-height: 1.5;
    white-space: normal !important; /* Override global pre-wrap */
    padding: 0 !important; /* Remove any padding that might cause spacing issues */
    margin: 0 !important; /* Remove any margins */
    grid-column: 2;
}

.ATT-attachments-container .ATT-file-wrapper {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.ATT-attachments-container .ATT-file-info-block {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 8px;
    line-height: 1.5;
}

.ATT-attachments-container .ATT-file-name-item {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
    user-select: text;
}

.ATT-attachments-container .ATT-file-name-item i {
    flex-shrink: 0;
}

.ATT-attachments-container .ATT-file-size-item {
    display: block;
    margin-bottom: 3px;
    user-select: text;
}

.ATT-attachments-container .ATT-file-description-item {
    display: block;
    margin-bottom: 0;
    user-select: text;
}

.ATT-attachments-container .ATT-file-buttons {
    margin-top: 8px;
    user-select: none;
}

.ATT-attachments-container .ATT-file-buttons .btn-group {
    display: inline-flex;
}
</style>


