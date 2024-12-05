{{-- resources/views/reports/monthly/partials/comments.blade.php --}}
<!-- Comments Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Comments</h4>
    </div>
    <div class="card-body">
        @if($report->comments->isEmpty())
            <p>No comments yet.</p>
        @else
            @foreach($report->comments as $comment)
                <div class="mb-3 comment-card">
                    <p><strong>{{ $comment->user->name }} ({{ ucfirst($comment->user->role) }}):</strong></p>
                    <p>{{ $comment->comment }}</p>
                    <p><small>{{ $comment->created_at->format('d-m-Y H:i') }}</small></p>
                </div>
            @endforeach
        @endif
    </div>
</div>

<!-- Comment Form -->
@php
    $user = auth()->user();
    $routeName = '';
    if ($user->role == 'provincial') {
        $routeName = 'provincial.monthly.report.addComment';
    } elseif ($user->role == 'coordinator') {
        $routeName = 'coordinator.monthly.report.addComment';
    }
@endphp

@if($user->role == 'provincial' || $user->role == 'coordinator')
    <div class="mb-3 card">
        <div class="card-header">
            <h4>Add Comment</h4>
        </div>
        <div class="card-body">
            <form action="{{ route($routeName, $report->report_id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="comment" class="form-label">Your Comment</label>
                    <textarea name="comment" id="comment" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Comment</button>
            </form>
        </div>
    </div>
@endif
