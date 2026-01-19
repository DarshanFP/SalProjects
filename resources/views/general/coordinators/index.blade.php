@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Coordinator Management</h4>
                    <p class="mb-0 text-muted">Manage all coordinators under your management</p>
                    <a href="{{ route('general.createCoordinator') }}" class="float-right btn btn-primary">Create Coordinator</a>
                </div>
                <div class="card-body">
                    {{-- Success/Error Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {!! session('success') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('general.coordinators') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces ?? [] as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="center" class="form-label">Center</label>
                                <select name="center" id="center" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Centers</option>
                                    @foreach($centers ?? [] as $center)
                                        <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                            {{ $center }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-2 row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="{{ route('general.coordinators') }}" class="btn btn-secondary">Clear Filters</a>
                            </div>
                        </div>
                    </form>

                    <!-- Active Filters Display -->
                    @if(request('province') || request('center') || request('status'))
                    <div class="mb-4 alert alert-info">
                        <strong>Active Filters:</strong>
                        @if(request('province'))
                            <span class="badge badge-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('center'))
                            <span class="badge badge-success me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('status'))
                            <span class="badge badge-warning me-2">Status: {{ ucfirst(request('status')) }}</span>
                        @endif
                        <a href="{{ route('general.coordinators') }}" class="float-right btn btn-sm btn-outline-secondary">Clear All</a>
                    </div>
                    @endif

                    <!-- Summary Cards -->
                    <div class="mb-4 row">
                        <div class="col-md-3">
                            <div class="text-white card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Total Coordinators</h5>
                                    <h3 class="card-text">{{ $coordinators->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-white card bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Active Coordinators</h5>
                                    <h3 class="card-text">{{ $coordinators->where('status', 'active')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-white card bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Inactive Coordinators</h5>
                                    <h3 class="card-text">{{ $coordinators->where('status', 'inactive')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-white card bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Provinces</h5>
                                    <h3 class="card-text">{{ $coordinators->pluck('province')->unique()->count() }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($coordinators->count() > 0)
                    <div class="table-responsive" style="position: relative;">
                        <style>
                            .table thead th {
                                position: sticky;
                                top: 0;
                                background: inherit;
                                z-index: 2;
                                font-weight: bold;
                            }
                            .table th:first-child,
                            .table td:first-child {
                                position: sticky;
                                left: 0;
                                background: #181c2f; /* Solid background for dark theme */
                                z-index: 1;
                                font-weight: bold;
                            }
                            .table thead th:first-child {
                                z-index: 3;
                            }
                        </style>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>NAME</th>
                                    <th>USERNAME</th>
                                    <th>EMAIL</th>
                                    <th>PHONE</th>
                                    <th>PROVINCE</th>
                                    <th>CENTER</th>
                                    <th>ADDRESS</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($coordinators as $coordinator)
                                <tr>
                                    <td>{{ $coordinator->name }}</td>
                                    <td>{{ $coordinator->username ?: 'N/A' }}</td>
                                    <td>{{ $coordinator->email }}</td>
                                    <td>{{ $coordinator->phone ?: 'N/A' }}</td>
                                    <td><span class="badge badge-info">{{ $coordinator->province }}</span></td>
                                    <td>{{ $coordinator->center ?: 'N/A' }}</td>
                                    <td>{{ $coordinator->address ?: 'N/A' }}</td>
                                    <td>
                                        @if($coordinator->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('general.editCoordinator', $coordinator->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                            @if($coordinator->status === 'active')
                                                <form action="{{ route('general.deactivateUser', $coordinator->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to deactivate this coordinator?')">Deactivate</button>
                                                </form>
                                            @else
                                                <form action="{{ route('general.activateUser', $coordinator->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to activate this coordinator?')">Activate</button>
                                                </form>
                                            @endif
                                            <button type="button" class="btn btn-danger btn-sm" onclick="showResetPasswordModal({{ $coordinator->id }}, '{{ $coordinator->name }}')">Reset Password</button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <p class="mb-0">No coordinators found. <a href="{{ route('general.createCoordinator') }}">Create your first coordinator</a> to get started.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="resetPasswordForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p>Reset password for coordinator: <strong id="coordinatorName"></strong></p>
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showResetPasswordModal(coordinatorId, coordinatorName) {
    document.getElementById('coordinatorName').textContent = coordinatorName;
    document.getElementById('resetPasswordForm').action = '{{ route("general.resetUserPassword", ":id") }}'.replace(':id', coordinatorId);
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation for all action buttons
    const actionButtons = document.querySelectorAll('button[type="submit"]');
    actionButtons.forEach(button => {
        if (!button.hasAttribute('onclick')) {
            button.addEventListener('click', function(e) {
                const action = this.textContent.trim();
                if (!confirm(`Are you sure you want to ${action.toLowerCase()} this coordinator?`)) {
                    e.preventDefault();
                }
            });
        }
    });
});
</script>

@endsection
