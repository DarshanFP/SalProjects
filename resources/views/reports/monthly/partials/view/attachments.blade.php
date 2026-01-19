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
                                @php
                                    $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path);
                                @endphp

                                @if($fileExists)
                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="text-primary">
                                        <i class="fas fa-file"></i> {{ $attachment->file_name }}
                                    </a>
                                    <a href="{{ route('reports.attachments.download', $attachment->id) }}" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                @else
                                    <span class="text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> {{ $attachment->file_name }} (File not found)
                                    </span>
                                @endif
                                <br><br>
                                <span class="ms-2 text-muted">({{ $attachment->description ?? 'No description provided' }})</span>
                            </li>
                        </div>
                    @endforeach
                </ul>
            </div>
        @else
            <p class="text-muted">No attachments available.</p>
        @endif
    </div>
</div>
<style>
.att-grp {
    border-bottom: 1px solid #ddd;
    padding-bottom: 15px;
}

.att-grp:last-child {
    border-bottom: none;
}
</style>
