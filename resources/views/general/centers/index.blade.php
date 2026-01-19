@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Center Management</h4>
                        <p class="mb-0 text-muted">Manage centers and their associations with societies</p>
                    </div>
                    <a href="{{ route('general.createCenter') }}" class="btn btn-primary">
                        <i data-feather="plus"></i> Create Center
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Filters -->
                    <form method="GET" action="{{ route('general.centers') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="province_id" class="form-label">Filter by Province</label>
                                <select name="province_id" id="province_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->id }}" {{ request('province_id') == $province->id ? 'selected' : '' }}>
                                            {{ $province->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="society_id" class="form-label">Filter by Society (shows centers from society's province)</label>
                                <select name="society_id" id="society_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Centers</option>
                                    @foreach($societies as $society)
                                        <option value="{{ $society->id }}" {{ request('society_id') == $society->id ? 'selected' : '' }}>
                                            {{ $society->name }} ({{ $society->province->name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="is_active" class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select" onchange="this.form.submit()">
                                    <option value="1" {{ request('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                    <option value="" {{ request('is_active') === '' ? 'selected' : '' }}>All</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    @if ($centers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Center Name</th>
                                        <th>Society</th>
                                        <th>Province</th>
                                        <th>Users Count</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($centers as $index => $center)
                                        <tr>
                                            <td>{{ $centers->firstItem() + $index }}</td>
                                            <td><strong>{{ $center->name }}</strong></td>
                                            <td>
                                                @if($center->province && $center->province->societies->count() > 0)
                                                    <span class="badge bg-info">{{ $center->province->societies->count() }} Societies</span>
                                                @else
                                                    <span class="text-muted">No societies</span>
                                                @endif
                                            </td>
                                            <td>{{ $center->province ? $center->province->name : 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ $center->users()->count() }}</span>
                                            </td>
                                            <td>
                                                @if($center->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('general.editCenter', $center->id) }}" class="btn btn-sm btn-primary">
                                                    <i data-feather="edit"></i> Edit
                                                </a>
                                                <form action="{{ route('general.deleteCenter', $center->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this center? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i data-feather="trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $centers->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i data-feather="info"></i> No centers found.
                            <a href="{{ route('general.createCenter') }}">Create your first center</a>.
                        </div>
                    @endif
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
