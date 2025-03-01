{{-- resources/views/projects/partials/Show/attachments.blade.php --}}
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
                <div class="info-value"><a href="{{ route('download.attachment', $attachment->id) }}">{{ $attachment->file_name }}</a></div>

                <div class="info-label"><strong>Description:</strong></div>
                <div class="info-value">{{ $attachment->description }}</div>
            @endforeach
        </div>
        <span style="font-style: italic; font-weight: normal;">(click on attachment name to download it)</span>

    </div>
</div>
