@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Edit Executor/Applicant</h4>
                    <p class="mb-0 text-muted">Update member information</p>
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

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('general.updateExecutor', $executor->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $executor->name) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="{{ old('username', $executor->username) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $executor->email) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $executor->phone) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label for="society_id">Society Name <span class="text-danger">*</span></label>
                            <select class="form-control" id="society_id" name="society_id" required>
                                <option value="" disabled>Select Society / Trust</option>
                                @foreach($societies ?? [] as $society)
                                    <option value="{{ $society->id }}" {{ old('society_id', $executor->society_id ?? '') == $society->id ? 'selected' : '' }}>{{ $society->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="role">Role <span class="text-danger">*</span></label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="executor" {{ old('role', $executor->role) == 'executor' ? 'selected' : '' }}>Executor</option>
                                <option value="applicant" {{ old('role', $executor->role) == 'applicant' ? 'selected' : '' }}>Applicant</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="province">Province <span class="text-danger">*</span></label>
                            <select name="province" class="form-control" required id="province">
                                <option value="" disabled>Choose one</option>
                                @foreach($provinces ?? [] as $province)
                                    <option value="{{ $province->name }}" {{ old('province', $executor->province) == $province->name ? 'selected' : '' }}>
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="center">Center</label>
                            <select class="form-control" id="center" name="center">
                                <option value="" disabled>Choose province first</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <textarea class="form-control auto-resize-textarea" id="address" name="address">{{ old('address', $executor->address) }}</textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="active" {{ old('status', $executor->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $executor->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">Update Member</button>
                            <a href="{{ route('general.executors') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h5 class="mt-4 mb-3">Reset Password</h5>
                    <form action="{{ route('general.resetUserPassword', $executor->id) }}" method="POST" id="resetPasswordForm">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="password">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <small id="passwordHelp" class="form-text text-muted">Minimum 8 characters</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password_confirmation">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
                            <small id="confirmPasswordHelp" class="form-text"></small>
                        </div>
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to reset the password for {{ $executor->name }}?')">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    const passwordHelp = document.getElementById('passwordHelp');
    const confirmPasswordHelp = document.getElementById('confirmPasswordHelp');
    const provinceSelect = document.getElementById('province');
    const centerSelect = document.getElementById('center');
    const currentCenter = '{{ $executor->center }}';

    // Centers mapping (from controller)
    const centersMap = @json($centersMap);

    // Password validation
    password.onkeyup = function () {
        if (password.value.length < 8) {
            passwordHelp.textContent = 'Password must be at least 8 characters long.';
            passwordHelp.style.color = 'red';
        } else {
            passwordHelp.textContent = '';
        }
    };

    confirmPassword.onkeyup = function () {
        if (confirmPassword.value !== password.value) {
            confirmPasswordHelp.textContent = 'Passwords do not match.';
            confirmPasswordHelp.style.color = 'red';
        } else {
            confirmPasswordHelp.textContent = 'Passwords match.';
            confirmPasswordHelp.style.color = 'green';
        }
    };

    // Function to populate centers
    function populateCenters(province) {
        centerSelect.innerHTML = '<option value="" disabled>Choose center</option>';

        if (province && centersMap[province.toUpperCase()]) {
            centersMap[province.toUpperCase()].forEach(function(center) {
                const option = document.createElement('option');
                option.value = center;
                option.textContent = center;
                if (center === currentCenter) {
                    option.selected = true;
                }
                centerSelect.appendChild(option);
            });
        }
    }

    // Initialize centers based on current province
    populateCenters(provinceSelect.value);

    // Handle province change
    provinceSelect.addEventListener('change', function() {
        populateCenters(this.value);
    });
});
</script>
@endsection
