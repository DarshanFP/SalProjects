<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachments</h4>
    </div>
    <div class="card-body">
        @foreach($project->attachments as $attachment)
            <div class="attachment-grid">
                <div class="attachment-label"><strong>Attachment:</strong></div>
                <div class="attachment-value"><a href="{{ route('download.attachment', $attachment->id) }}">{{ $attachment->file_name }}</a></div>
                <div class="attachment-label"><strong>Description:</strong></div>
                <div class="attachment-value">{{ $attachment->description }}</div>
            </div>
        @endforeach
    </div>
</div>
