@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Centers in {{ $province->name }}</h4>
                    <a href="{{ route('provincial.createCenter') }}" class="btn btn-primary">
                        <i data-feather="plus"></i> Create New Center
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($centers->isEmpty())
                        <div class="alert alert-info">
                            <i data-feather="info"></i> No centers found in {{ $province->name }}.
                            <a href="{{ route('provincial.createCenter') }}">Create your first center</a>.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Center Name</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($centers as $index => $center)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $center->name }}</strong>
                                            </td>
                                            <td>
                                                @if($center->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>{{ $center->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <a href="{{ route('provincial.editCenter', $center->id) }}" class="btn btn-sm btn-primary">
                                                    <i data-feather="edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
