<div class="mb-3 card">
    <div class="card-header">
        <h4>3. Attachments</h4>
    </div>
    <div class="card-body">
        <div id="attachments-container">
            @foreach($project->attachments ?? [] as $attachmentIndex => $attachment)
                <div class="mb-3 attachment-group" data-index="{{ $attachmentIndex }}">
                    <label class="form-label">Attachment {{ $attachmentIndex + 1 }}</label>
                    <input type="hidden" name="attachments[{{ $attachmentIndex }}][id]" value="{{ $attachment->id }}">
                    <input type="text" name="attachments[{{ $attachmentIndex }}][file_name]" class="form-control" placeholder="Name of File Attached" value="{{ $attachment->file_name }}">
                    <textarea name="attachments[{{ $attachmentIndex }}][description]" class="form-control" rows="3" placeholder="Brief Description">{{ $attachment->description }}</textarea>
                    <a href="{{ route('download.attachment', $attachment->id) }}" class="mt-2 btn btn-secondary">Download Existing Attachment</a>
                    <button type="button" class="mt-2 btn btn-danger remove-attachment">Remove</button>
                </div>
            @endforeach
        </div>
        <button type="button" class="mt-3 btn btn-primary add-attachment">Add More Attachment</button>
    </div>
</div>
