@extends('coordinator.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">All Users Management</h4>
                    <p class="mb-0 text-muted">Manage all users including Provincials, Executors, and Applicants</p>
                    <a href="{{ route('coordinator.createProvincial') }}" class="float-right btn btn-primary">Create User</a>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('coordinator.provincials') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="province" class="form-label">Province</label>
                                <select name="province" id="province" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="center" class="form-label">Center</label>
                                <select name="center" id="center" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Centers</option>
                                    @foreach($centers as $center)
                                        <option value="{{ $center }}" {{ request('center') == $center ? 'selected' : '' }}>
                                            {{ $center }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="role" class="form-label">Role</label>
                                <select name="role" id="role" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Roles</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                            {{ ucfirst($role) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="parent_id" class="form-label">Parent (Provincial)</label>
                                <select name="parent_id" id="parent_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Parents</option>
                                    @foreach($parents as $parent)
                                        <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }} ({{ $parent->province }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-2 row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="{{ route('coordinator.provincials') }}" class="btn btn-secondary">Clear Filters</a>
                            </div>
                        </div>
                    </form>

                    <!-- Active Filters Display -->
                    @if(request('province') || request('center') || request('role') || request('parent_id'))
                    <div class="mb-4 alert alert-info">
                        <strong>Active Filters:</strong>
                        @if(request('province'))
                            <span class="badge badge-primary me-2">Province: {{ request('province') }}</span>
                        @endif
                        @if(request('center'))
                            <span class="badge badge-success me-2">Center: {{ request('center') }}</span>
                        @endif
                        @if(request('role'))
                            <span class="badge badge-warning me-2">Role: {{ ucfirst(request('role')) }}</span>
                        @endif
                        @if(request('parent_id'))
                            @php
                                $selectedParent = $parents->firstWhere('id', request('parent_id'));
                            @endphp
                            @if($selectedParent)
                                <span class="badge badge-info me-2">Parent: {{ $selectedParent->name }}</span>
                            @endif
                        @endif
                        <a href="{{ route('coordinator.provincials') }}" class="float-right btn btn-sm btn-outline-secondary">Clear All</a>
                    </div>
                    @endif

                    <!-- Summary Cards -->
                    <div class="mb-4 row">
                        <div class="col-md-3">
                            <div class="text-white card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Total Users</h5>
                                    <h3 class="card-text">{{ $users->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-white card bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Provinces</h5>
                                    <h3 class="card-text">{{ $users->pluck('province')->unique()->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-white card bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Centers</h5>
                                    <h3 class="card-text">{{ $users->pluck('center')->filter()->unique()->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-white card bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Roles</h5>
                                    <h3 class="card-text">{{ $users->pluck('role')->unique()->count() }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Province</th>
                                    <th>Role</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Center</th>
                                    <th>Society Name</th>
                                    <th>Parent</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td>
                                        <span class="badge badge-info">{{ $user->province }}</span>
                                    </td>
                                    <td>
                                        @if($user->role === 'coordinator')
                                            <span class="badge badge-primary">{{ ucfirst($user->role) }}</span>
                                        @elseif($user->role === 'provincial')
                                            <span class="badge badge-success">{{ ucfirst($user->role) }}</span>
                                        @elseif($user->role === 'executor')
                                            <span class="badge badge-warning">{{ ucfirst($user->role) }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ ucfirst($user->role) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->username ?: 'N/A' }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->phone ?: 'N/A' }}</td>
                                    <td>{{ $user->center ?: 'N/A' }}</td>
                                    <td>{{ $user->society_name ?: 'N/A' }}</td>
                                    <td>
                                        @if($user->parent)
                                            <span class="badge badge-light">{{ $user->parent->name }}</span>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('coordinator.editProvincial', $user->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                            @if($user->status === 'active')
                                                <form action="{{ route('coordinator.deactivateUser', $user->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to deactivate this user?')">Deactivate</button>
                                                </form>
                                            @else
                                                <form action="{{ route('coordinator.activateUser', $user->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to activate this user?')">Activate</button>
                                                </form>
                                            @endif
                                            <form action="{{ route('coordinator.resetProvincialPassword', $user->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reset the password?')">Reset Password</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Users management page loaded');

    // Debug form submission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const formData = new FormData(form);
        console.log('Form submitting with data:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
    });

    // Debug current values for the three main filtering columns
    console.log('Current province:', '{{ request("province") }}');
    console.log('Current center:', '{{ request("center") }}');
    console.log('Current role:', '{{ request("role") }}');
    console.log('Current parent_id:', '{{ request("parent_id") }}');

    // Debug available options for the three main filtering columns
    console.log('Available provinces:', @json($provinces));
    console.log('Available centers:', @json($centers));
    console.log('Available roles:', @json($roles));
    console.log('Available parents:', @json($parents));

    // Add confirmation for all action buttons
    const actionButtons = document.querySelectorAll('button[type="submit"]');
    actionButtons.forEach(button => {
        if (!button.hasAttribute('onclick')) {
            button.addEventListener('click', function(e) {
                const action = this.textContent.trim();
                if (!confirm(`Are you sure you want to ${action.toLowerCase()} this user?`)) {
                    e.preventDefault();
                }
            });
        }
    });

    // Show current filter status
    const activeFilters = [];
    if ('{{ request("province") }}') activeFilters.push('Province: {{ request("province") }}');
    if ('{{ request("center") }}') activeFilters.push('Center: {{ request("center") }}');
    if ('{{ request("role") }}') activeFilters.push('Role: {{ request("role") }}');
    if ('{{ request("parent_id") }}') activeFilters.push('Parent: {{ request("parent_id") }}');

    if (activeFilters.length > 0) {
        console.log('Active filters:', activeFilters.join(', '));
    } else {
        console.log('No active filters - showing all users');
    }
});
</script>

@endsection
