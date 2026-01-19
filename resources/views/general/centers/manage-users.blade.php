@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fp-text-center1">Manage User Centers</h4>
                        <p class="mb-0 text-muted">Update centers for child users and their nested children</p>
                    </div>
                    <a href="{{ route('general.manageCenters') }}" class="btn btn-secondary">Manage Centers</a>
                </div>
                <div class="card-body">
                    {{-- Success/Error Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {!! session('success') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Summary -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Child Users</h5>
                                    <h3 class="card-text">{{ $childUsers->count() }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($childUsers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Current Province</th>
                                        <th>Current Center</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($childUsers as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td><span class="badge bg-info">{{ ucfirst($user->role) }}</span></td>
                                            <td>{{ $user->province ?? 'N/A' }}</td>
                                            <td>{{ $user->center ?? 'N/A' }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCenterModal{{ $user->id }}">
                                                    <i data-feather="edit"></i> Update Center
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Edit Center Modal -->
                                        <div class="modal fade" id="editCenterModal{{ $user->id }}" tabindex="-1" aria-labelledby="editCenterModalLabel{{ $user->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('general.updateUserCenter', $user->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editCenterModalLabel{{ $user->id }}">Update Center for {{ $user->name }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group mb-3">
                                                                <label for="province{{ $user->id }}">Province <span class="text-danger">*</span></label>
                                                                <select name="province" id="province{{ $user->id }}" class="form-control" required onchange="updateCenterDropdown({{ $user->id }})">
                                                                    <option value="" disabled>Select province</option>
                                                                    @foreach($provinces as $province)
                                                                        <option value="{{ $province->name }}" {{ old('province', $user->province) == $province->name ? 'selected' : '' }}>
                                                                            {{ $province->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="form-group mb-3">
                                                                <label for="center{{ $user->id }}">Center</label>
                                                                <select name="center" id="center{{ $user->id }}" class="form-control">
                                                                    <option value="" disabled selected>Select province first</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group mb-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="update_child_users" id="update_child_users{{ $user->id }}" value="1">
                                                                    <label class="form-check-label" for="update_child_users{{ $user->id }}">
                                                                        Update center for child users (recursive)
                                                                    </label>
                                                                    <small class="form-text text-muted d-block">
                                                                        If checked, all child users (including nested) will have their province and center updated to match.
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Update Center</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i data-feather="info"></i> No child users found under your management.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Centers mapping for JavaScript
    const centersMap = @json($centersMap);

    // Initialize centers dropdown for each user modal
    @foreach($childUsers as $user)
        @if($user->province)
            updateCenterDropdown({{ $user->id }}, '{{ $user->center }}');
        @endif
    @endforeach
});

function updateCenterDropdown(userId, currentCenter = null) {
    const provinceSelect = document.getElementById('province' + userId);
    const centerSelect = document.getElementById('center' + userId);
    const selectedProvince = provinceSelect.value;

    centerSelect.innerHTML = '<option value="" disabled>Select center</option>';

    if (selectedProvince && centersMap[selectedProvince.toUpperCase()]) {
        centersMap[selectedProvince.toUpperCase()].forEach(function(center) {
            const option = document.createElement('option');
            option.value = center;
            option.textContent = center;
            if (currentCenter && center === currentCenter) {
                option.selected = true;
            }
            centerSelect.appendChild(option);
        });
    }
}
</script>
@endsection
