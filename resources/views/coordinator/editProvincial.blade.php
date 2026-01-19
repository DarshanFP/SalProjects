@extends('coordinator.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Edit Provincial</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('coordinator.updateProvincial', $provincial->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $provincial->name }}" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" class="form-control" value="{{ $provincial->username }}">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $provincial->email }}" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ $provincial->phone }}">
                        </div>
                        <div class="form-group">
                            <label for="province">Province</label>
                            <select name="province" class="form-control" required id="province">
                                <option value="" disabled>Choose one</option>
                                @foreach($provinces ?? [] as $province)
                                    <option value="{{ $province->name }}" {{ old('province', $provincial->province) == $province->name ? 'selected' : '' }}>
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="center">Center</label>
                            <select name="center" class="form-control" id="center">
                                <option value="" disabled selected>Choose province first</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" name="address" class="form-control" value="{{ $provincial->address }}">
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" class="form-control" required>
                                <option value="" disabled selected>Choose one</option>
                                <option value="coordinator" {{ $provincial->role == 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                                <option value="provincial" {{ $provincial->role == 'provincial' ? 'selected' : '' }}>Provincial</option>
                                <option value="executor" {{ $provincial->role == 'executor' ? 'selected' : '' }}>Executor</option>
                                <option value="applicant" {{ $provincial->role == 'applicant' ? 'selected' : '' }}>Applicant</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" class="form-control" required>
                                <option value="" disabled selected>Choose one</option>
                                <option value="active" {{ $provincial->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $provincial->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>

                    <h4 class="mt-4 fp-text-center1">Reset Password</h4>
                    <form action="{{ route('coordinator.resetUserPassword', $provincial->id) }}" method="POST" id="resetPasswordForm">
                        @csrf
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small id="passwordHelp" class="form-text text-muted"></small>
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirm New Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            <small id="confirmPasswordHelp" class="form-text"></small>
                        </div>
                        <button type="submit" class="btn btn-warning">Reset Password</button>
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
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('province');
    const centerSelect = document.getElementById('center');
    const currentCenter = '{{ $provincial->center }}';

    // Centers mapping
    const centersMap = @json($centersMap);

    // Function to populate centers
    function populateCenters(province) {
        centerSelect.innerHTML = '<option value="" disabled selected>Choose center</option>';

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
