@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Create Province</h4>
                    <p class="mb-0 text-muted">Create a new province in the system</p>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('general.storeProvince') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="name">Province Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g., Mumbai, Delhi, etc.">
                            <small class="form-text text-muted">Enter a unique province name. This will be used throughout the system.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="centers">Centers (Optional)</label>
                            <textarea name="centers" id="centers" class="form-control auto-resize-textarea" rows="5" placeholder="Enter centers one per line (optional - can be added later)">{{ old('centers') }}</textarea>
                            <small class="form-text text-muted">Enter centers one per line. You can add centers now or later when editing the province.</small>
                        </div>
                        <div class="alert alert-info">
                            <i data-feather="info"></i> <strong>Note:</strong> After creating the province, you'll need to assign a provincial coordinator to activate it. The provincial coordinator can be any user in the system, including yourself.
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save"></i> Create Province
                            </button>
                            <a href="{{ route('general.provinces') }}" class="btn btn-secondary">Cancel</a>
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
