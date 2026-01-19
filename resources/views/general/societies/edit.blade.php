@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Edit Society</h4>
                    <p class="mb-0 text-muted">Update society information</p>
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

                    <form action="{{ route('general.updateSociety', $society->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-3">
                            <label for="province_id">Province <span class="text-danger">*</span></label>
                            <select name="province_id" id="province_id" class="form-control" required>
                                <option value="" disabled>Select Province</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}" {{ old('province_id', $society->province_id) == $province->id ? 'selected' : '' }}>
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="name">Society Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $society->name) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="is_active">Status <span class="text-danger">*</span></label>
                            <select name="is_active" id="is_active" class="form-control" required>
                                <option value="1" {{ old('is_active', $society->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $society->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="alert alert-warning">
                            <i data-feather="alert-triangle"></i> <strong>Warning:</strong> Changing the province will affect all centers associated with this society.
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save"></i> Update Society
                            </button>
                            <a href="{{ route('general.societies') }}" class="btn btn-secondary">Cancel</a>
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
