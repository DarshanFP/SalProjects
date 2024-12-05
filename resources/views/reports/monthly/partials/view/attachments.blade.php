{{-- resources/views/reports/monthly/partials/view/attachments.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Attachments</h4>
    </div>
    <div class="card-body">
        {{-- Display Existing Attachments --}}
        @if($report->attachments && $report->attachments->count() > 0)
            <div class="mb-3">
                <label class="form-label">Existing Attachments</label>
                <ul>
                    @foreach($report->attachments as $attachment)
                        <div class="card-body att-grp">
                            <li>
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">{{ $attachment->file_name }}</a><br><br>
                                <span class="ms-2">({{ $attachment->description ?? 'No description provided' }})</span>
                            </li>
                        </div>
                    @endforeach
                </ul>
            </div>
        @else
            <p>No attachments available.</p>
        @endif
    </div>
</div>
<style>
.att-grp {
    border-bottom: 1px solid #ddd;
    padding-bottom: 15px;
}
</style>
