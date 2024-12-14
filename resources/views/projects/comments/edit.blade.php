{{-- resources/views/projects/comments/edit.blade.php --}}
@extends($layout) {{-- same logic used for show.blade.php for layout based on role --}}

@section('content')
<div class="container">
    <h1>Edit Comment</h1>
    <form action="{{ in_array(Auth::user()->role, ['provincial']) ? route('provincial.projects.updateComment', $comment->id) : route('coordinator.projects.updateComment', $comment->id) }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="comment" class="form-label">Comment</label>
            <textarea name="comment" id="comment" class="form-control" rows="3" required>{{ old('comment', $comment->comment) }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update Comment</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection

