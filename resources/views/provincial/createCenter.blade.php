@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Create Center</h4>
                    <p class="mb-0 text-muted">Create a new center in {{ $province->name }} province</p>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('provincial.storeCenter') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="name">Center Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g., Center Name">
                            <small class="form-text text-muted">Enter a unique center name for {{ $province->name }} province.</small>
                        </div>

                        @if (!empty($existingCenters))
                        <div class="alert alert-info">
                            <i data-feather="info"></i> <strong>Existing Centers in {{ $province->name }}:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($existingCenters as $center)
                                    <li>{{ $center }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save"></i> Create Center
                            </button>
                            <a href="{{ route('provincial.createExecutor') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endpush
@endsection
