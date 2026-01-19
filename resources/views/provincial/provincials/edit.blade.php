@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Edit Provincial User</h4>
                    <p class="mb-0 text-muted">Update provincial user information</p>
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

                    <form action="{{ route('provincial.updateProvincial', $targetProvincial->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-3">
                            <label for="province">Province</label>
                            <input type="text" class="form-control" value="{{ $province->name }}" disabled>
                            <small class="form-text text-muted">Provincial user belongs to {{ $province->name }} province.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $targetProvincial->name) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="username" class="form-control" value="{{ old('username', $targetProvincial->username) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $targetProvincial->email) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $targetProvincial->phone) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label for="society_name">Society</label>
                            <select name="society_name" id="society_name" class="form-control">
                                <option value="">Select Society</option>
                                @foreach($societies as $society)
                                    <option value="{{ $society->name }}" {{ old('society_name', $targetProvincial->society_name) == $society->name ? 'selected' : '' }}>
                                        {{ $society->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="center">Center</label>
                            <select name="center" id="center" class="form-control">
                                <option value="">Select Center</option>
                                @foreach($centers as $center)
                                    <option value="{{ $center->name }}" {{ old('center', $targetProvincial->center) == $center->name ? 'selected' : '' }}>
                                        {{ $center->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <textarea name="address" id="address" class="form-control" rows="3">{{ old('address', $targetProvincial->address) }}</textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="active" {{ old('status', $targetProvincial->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $targetProvincial->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save"></i> Update Provincial
                            </button>
                            <a href="{{ route('provincial.provincials') }}" class="btn btn-secondary">Cancel</a>
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
