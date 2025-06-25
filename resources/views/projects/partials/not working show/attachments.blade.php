{{-- resources/views/projects/partials/Show/attachments.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachments</h4>
    </div>
    <div class="card-body">
        @if($project->attachments->count() > 0)
            <div class="info-grid">
                @foreach($project->attachments as $attachment)
                    <div class="info-label">
                        <strong>Attachment:</strong>
                    </div>
                    <div class="info-value">
                        <a href="{{ route('download.attachment', $attachment->id) }}" target="_blank" class="btn btn-sm btn-primary">View Document</a>
                        <a href="{{ route('download.attachment', $attachment->id) }}" class="btn btn-sm btn-secondary">Download</a>
                    </div>

                    <div class="info-label"><strong>Description:</strong></div>
                    <div class="info-value">{{ $attachment->description ?? 'No description provided' }}</div>
                @endforeach
            </div>
        @else
            <div class="info-grid">
                <div class="info-label"><strong>Status:</strong></div>
                <div class="info-value text-muted">No attachments uploaded yet.</div>
            </div>
        @endif
    </div>
</div>
