{{-- resources/views/projects/partials/Show/attachments.blade.php
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachments</h4>
    </div>
    <div class="card-body">
        @foreach($project->attachments as $attachment)
            <div class="attachment-grid">
                <div class="attachment-label"><strong>Attachment:</strong></div>
                <div class="attachment-value"><a href="{{ route('projects.attachments.download', $attachment->id) }}">{{ $attachment->file_name }}</a></div>
                <div class="attachment-label"><strong>Description:</strong></div>
                <div class="attachment-value">{{ $attachment->description }}</div>
            </div>
        @endforeach
    </div>
</div> --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachments</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            @foreach($project->attachments as $attachment)
                <div class="info-label">
                    <strong>Attachment:</strong>
                </div>
                <div class="info-value"><a href="{{ route('projects.attachments.download', $attachment->id) }}">{{ $attachment->file_name }}</a></div>

                <div class="info-label"><strong>Description:</strong></div>
                <div class="info-value">{{ $attachment->description }}</div>
            @endforeach
        </div>
        <span style="font-style: italic; font-weight: normal;">(click on attachment name to download it)</span>

    </div>
</div>
