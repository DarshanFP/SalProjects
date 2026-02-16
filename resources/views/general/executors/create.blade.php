@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Create Executor/Applicant</h4>
                    <p class="mb-0 text-muted">Add a new executor or applicant to your direct team</p>
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

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('general.storeExecutor') }}" method="POST" id="executorForm">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <span toggle="#password" class="fa fa-fw fa-eye field-icon toggle-password" style="cursor: pointer;"></span>
                            <small id="passwordHelp" class="form-text text-muted">Minimum 8 characters</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
                            <span toggle="#password_confirmation" class="fa fa-fw fa-eye field-icon toggle-password" style="cursor: pointer;"></span>
                            <small id="confirmPasswordHelp" class="form-text"></small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label for="role">Role <span class="text-danger">*</span></label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="" disabled selected>Select Role</option>
                                <option value="executor" {{ old('role') == 'executor' ? 'selected' : '' }}>Executor</option>
                                <option value="applicant" {{ old('role') == 'applicant' ? 'selected' : '' }}>Applicant</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="province">Province <span class="text-danger">*</span></label>
                            <select name="province" class="form-control" required id="province">
                                <option value="" disabled selected>Choose one</option>
                                @foreach($provinces ?? [] as $province)
                                    <option value="{{ $province->name }}" {{ old('province') == $province->name ? 'selected' : '' }}>
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="society_id">Society Name <span class="text-danger">*</span></label>
                            <select class="form-control" id="society_id" name="society_id" required disabled>
                                <option value="" disabled selected>Select Province first</option>
                            </select>
                            <small class="form-text text-muted">Select the society. All centers from the province will be available.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="center">Center</label>
                            <select class="form-control" id="center" name="center">
                                <option value="" disabled selected>Choose province first</option>
                            </select>
                            <small class="form-text text-muted">All centers from the selected province are available.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <textarea class="form-control auto-resize-textarea" id="address" name="address">{{ old('address') }}</textarea>
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">Create Member</button>
                            <a href="{{ route('general.executors') }}" class="btn btn-secondary">Cancel</a>
                        </div>
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
    const togglePasswords = document.querySelectorAll('.toggle-password');
    const provinceSelect = document.getElementById('province');
    const societySelect = document.getElementById('society_id');
    const centerSelect = document.getElementById('center');

    // Centers mapping (from controller) - centers belong to provinces
    const centersMap = @json($centersMap);

    // Phase 5B3: Societies by province (id + name)
    const societiesMap = @json(collect($societiesByProvince ?? [])->map(function($societies) {
        return $societies->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values()->all();
    })->all());

    // Toggle password visibility
    togglePasswords.forEach(toggle => {
        toggle.onclick = function () {
            const input = document.querySelector(toggle.getAttribute('toggle'));
            if (input.type === 'password') {
                input.type = 'text';
                toggle.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                toggle.classList.remove('fa-eye-slash');
            }
        };
    });

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

    // Set initial values if editing
    @if(old('province'))
        const oldProvince = '{{ old("province") }}';
        const oldSocietyId = '{{ old("society_id") }}';
        const oldCenter = '{{ old("center") }}';

        // Populate societies (Phase 5B3: society_id)
        if (societiesMap[oldProvince]) {
            societiesMap[oldProvince].forEach(function(society) {
                const option = document.createElement('option');
                option.value = society.id;
                option.textContent = society.name;
                if (String(society.id) === oldSocietyId) {
                    option.selected = true;
                }
                societySelect.appendChild(option);
            });
            societySelect.disabled = false;
        }

        // Populate centers (all centers from province)
        if (centersMap[oldProvince.toUpperCase()]) {
            centersMap[oldProvince.toUpperCase()].forEach(function(center) {
                const option = document.createElement('option');
                option.value = center;
                option.textContent = center;
                if (center === oldCenter) {
                    option.selected = true;
                }
                centerSelect.appendChild(option);
            });
        }
    @endif

    // Handle province change
    provinceSelect.addEventListener('change', function() {
        const selectedProvince = this.value;

        // Reset and populate societies (Phase 5B3: society_id)
        societySelect.innerHTML = '<option value="" disabled selected>Select Society</option>';
        societySelect.disabled = !selectedProvince;

        if (selectedProvince && societiesMap[selectedProvince]) {
            societiesMap[selectedProvince].forEach(function(society) {
                const option = document.createElement('option');
                option.value = society.id;
                option.textContent = society.name;
                societySelect.appendChild(option);
            });
        }

        // Reset and populate centers (ALL centers from province, regardless of society)
        centerSelect.innerHTML = '<option value="" disabled selected>Choose center</option>';
        if (selectedProvince && centersMap[selectedProvince.toUpperCase()]) {
            centersMap[selectedProvince.toUpperCase()].forEach(function(center) {
                const option = document.createElement('option');
                option.value = center;
                option.textContent = center;
                centerSelect.appendChild(option);
            });
        }
    });

    // Handle society change (centers remain the same - all from province)
    societySelect.addEventListener('change', function() {
        // Centers don't change when society changes - they're all from the province
        // This is intentional: all centers in a province are available to all societies
    });
});
</script>
@endsection
