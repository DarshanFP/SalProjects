@extends('general.dashboard')

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

                    <form action="{{ route('general.updateProvincial', $provincial->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $provincial->name) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" class="form-control" value="{{ old('username', $provincial->username) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $provincial->email) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $provincial->phone) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label for="province">Province <span class="text-danger">*</span></label>
                            <select name="province" class="form-control" required id="province">
                                <option value="" disabled>Choose one</option>
                                @foreach($provinces ?? [] as $province)
                                    <option value="{{ $province->name }}" {{ old('province', $provincial->province) == $province->name ? 'selected' : '' }}>
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="society_id">Society</label>
                            <select name="society_id" class="form-control" id="society_id">
                                <option value="">Select Society</option>
                                @foreach($societies ?? [] as $society)
                                    <option value="{{ $society->id }}" {{ old('society_id', $provincial->society_id ?? '') == $society->id ? 'selected' : '' }}>{{ $society->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select a society from the selected province</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="center">Center</label>
                            <select name="center" class="form-control" id="center">
                                <option value="" disabled>Choose province first</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <input type="text" name="address" id="address" class="form-control" value="{{ old('address', $provincial->address) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="" disabled>Choose one</option>
                                <option value="active" {{ old('status', $provincial->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $provincial->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <small class="form-text text-muted">Role: Provincial (cannot be changed)</small>
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">Update Provincial</button>
                            <a href="{{ route('general.provincials') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h5 class="mt-4 mb-3">Reset Password</h5>
                    <form action="{{ route('general.resetUserPassword', $provincial->id) }}" method="POST" id="resetPasswordForm">
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
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to reset the password for {{ $provincial->name }}?')">Reset Password</button>
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

document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('province');
    const centerSelect = document.getElementById('center');
    const societySelect = document.getElementById('society_id');
    const currentCenter = '{{ $provincial->center }}';
    const currentSocietyId = '{{ $provincial->society_id ?? '' }}';

    // Centers mapping
    const centersMap = @json($centersMap);

    // Phase 5B3: Societies by province (id + name)
    const societiesByProvince = @json(collect($societiesByProvince ?? [])->map(function($societies) {
        return $societies->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values()->all();
    })->all());

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

    // Function to populate societies (Phase 5B3: society_id)
    function populateSocieties(province) {
        societySelect.innerHTML = '<option value="">Select Society</option>';

        if (province && societiesByProvince[province]) {
            societiesByProvince[province].forEach(function(society) {
                const option = document.createElement('option');
                option.value = society.id;
                option.textContent = society.name;
                if (String(society.id) === currentSocietyId) {
                    option.selected = true;
                }
                societySelect.appendChild(option);
            });
        }
    }

    // Initialize centers and societies based on current province
    populateCenters(provinceSelect.value);
    populateSocieties(provinceSelect.value);

    // Handle province change
    provinceSelect.addEventListener('change', function() {
        populateCenters(this.value);
        populateSocieties(this.value);
    });
});
</script>
@endsection
