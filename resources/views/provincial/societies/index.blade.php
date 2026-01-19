@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Society Management - {{ $province->name }}</h4>
                        <p class="mb-0 text-muted">Manage societies in your province</p>
                    </div>
                    <a href="{{ route('provincial.createSociety') }}" class="btn btn-primary">
                        <i data-feather="plus"></i> Create Society
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($societies->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Society Name</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($societies as $index => $society)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>{{ $society->name }}</strong></td>
                                            <td>
                                                @if($society->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>{{ $society->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <a href="{{ route('provincial.editSociety', $society->id) }}" class="btn btn-sm btn-primary">
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
                            <i data-feather="info"></i> No societies found in {{ $province->name }}.
                            <a href="{{ route('provincial.createSociety') }}">Create your first society</a>.
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
