{{-- resources/views/projects/partials/ProjectComments.blade.php --}}
<div class="mt-4 card">
    <div class="card-header">
        <h5>Comments</h5>
    </div>
    <div class="card-body">
        @if($project->comments->count() > 0)
            @foreach($project->comments as $c)
                <div class="p-2 mb-2 border rounded">
                    <strong>{{ $c->user->name }} ({{ $c->user->role }})</strong><br>
                    <span>{{ $c->comment }}</span><br>
                    <small class="text-muted">Posted on: {{ $c->created_at->format('d-m-Y H:i') }}</small>

                    @if(Auth::id() === $c->user_id && in_array(Auth::user()->role, ['provincial','coordinator']))
                        <!-- Edit Comment Link -->
                        @if(Auth::user()->role === 'provincial')
                            {{-- <a href="{{ route('provincial.projects.editComment', $c->id) }}" class="btn btn-sm btn-warning">Edit</a> --}}
                        @elseif(Auth::user()->role === 'coordinator')
                            {{-- <a href="{{ route('coordinator.projects.editComment', $c->id) }}" class="btn btn-sm btn-warning">Edit</a> --}}
                        @endif
                    @endif
                </div>
            @endforeach
        @else
            <p>No comments yet.</p>
        @endif

        @if(in_array(Auth::user()->role, ['provincial','coordinator']))
            <hr>
            <form action="{{ in_array(Auth::user()->role, ['provincial']) ? route('provincial.projects.addComment', $project->project_id) : route('coordinator.projects.addComment', $project->project_id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="comment" class="form-label">Add Comment</label>
                    <textarea name="comment" id="comment" rows="3" class="form-control auto-resize-textarea" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Submit Comment</button>
            </form>
        @endif
    </div>
</div>
