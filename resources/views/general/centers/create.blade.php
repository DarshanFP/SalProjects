@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Create Center</h4>
                    <p class="mb-0 text-muted">Create a new center and associate it with a society</p>
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

                    <form action="{{ route('general.storeCenter') }}" method="POST" id="centerForm">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="province_id">Province <span class="text-danger">*</span></label>
                            <select name="province_id" id="province_id" class="form-control" required>
                                <option value="" disabled selected>Select Province</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}" {{ old('province_id') == $province->id ? 'selected' : '' }}>
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select the province this center belongs to. Centers belong to provinces.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="name">Center Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g., Center Name">
                            <small class="form-text text-muted">Enter a unique center name for the selected province.</small>
                        </div>
                        <div class="alert alert-info">
                            <i data-feather="info"></i> <strong>Note:</strong> Centers belong to provinces. All centers in a province will be available to all societies in that province when creating users.
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save"></i> Create Center
                            </button>
                            <a href="{{ route('general.centers') }}" class="btn btn-secondary">Cancel</a>
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
