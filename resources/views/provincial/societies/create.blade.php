@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Create Society</h4>
                    <p class="mb-0 text-muted">Create a new society for {{ $province->name }}</p>
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

                    <form action="{{ route('provincial.storeSociety') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="province">Province</label>
                            <input type="text" class="form-control" value="{{ $province->name }}" disabled>
                            <input type="hidden" name="province_id" value="{{ $province->id }}">
                            <small class="form-text text-muted">Society will be created for {{ $province->name }} province.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="name">Society Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g., ST. ANN'S EDUCATIONAL SOCIETY">
                            <small class="form-text text-muted">Enter a unique society name for {{ $province->name }}.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <textarea name="address" id="address" class="form-control" rows="3">{{ old('address') }}</textarea>
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save"></i> Create Society
                            </button>
                            <a href="{{ route('provincial.societies') }}" class="btn btn-secondary">Cancel</a>
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
