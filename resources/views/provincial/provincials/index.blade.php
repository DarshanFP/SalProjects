@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Provincial Management - {{ $province->name }}</h4>
                        <p class="mb-0 text-muted">Manage provincial users in your province</p>
                    </div>
                    <a href="{{ route('provincial.createProvincial') }}" class="btn btn-primary">
                        <i data-feather="plus"></i> Create Provincial
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($provincials->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th>Center</th>
                                        <th>Society</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($provincials as $index => $provincialUser)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>{{ $provincialUser->name }}</strong></td>
                                            <td>{{ $provincialUser->email }}</td>
                                            <td>{{ $provincialUser->username }}</td>
                                            <td>{{ $provincialUser->center ?? 'N/A' }}</td>
                                            <td>{{ $provincialUser->society_name ?? 'N/A' }}</td>
                                            <td>
                                                @if($provincialUser->status === 'active')
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('provincial.editProvincial', $provincialUser->id) }}" class="btn btn-sm btn-primary">
                                                    <i data-feather="edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i data-feather="info"></i> No provincial users found in {{ $province->name }}.
                            <a href="{{ route('provincial.createProvincial') }}">Create your first provincial user</a>.
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
