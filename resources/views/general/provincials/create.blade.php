@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Create Provincial User</h4>
                    <p class="mb-0 text-muted">Create a new provincial user under your management</p>
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

                    <form action="{{ route('general.storeProvincial') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="8">
                            <small class="form-text text-muted">Minimum 8 characters</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8">
                        </div>
                        <div class="form-group mb-3">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}">
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
                            <label for="society_id">Society</label>
                            <select name="society_id" class="form-control" id="society_id">
                                <option value="">Select Society</option>
                            </select>
                            <small class="form-text text-muted">Select a society from the selected province</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="center">Center</label>
                            <select name="center" class="form-control" id="center">
                                <option value="" disabled selected>Choose province first</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <input type="text" name="address" id="address" class="form-control" value="{{ old('address') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="" disabled selected>Choose one</option>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <input type="hidden" name="role" value="provincial">
                            <small class="form-text text-muted">Role: Provincial (automatically assigned)</small>
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">Create Provincial</button>
                            <a href="{{ route('general.provincials') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('province');
    const centerSelect = document.getElementById('center');
    const societySelect = document.getElementById('society_id');

    // Centers mapping (from controller)
    const centersMap = @json($centersMap);

    // Phase 5B3: Societies by province (id + name)
    const societiesByProvince = @json(collect($societiesByProvince ?? [])->map(function($societies) {
        return $societies->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values()->all();
    })->all());

    // Set initial values if editing
    @if(old('center') && old('province'))
        const oldProvince = '{{ old("province") }}';
        const oldCenter = '{{ old("center") }}';
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

    @if(old('society_id') && old('province'))
        const oldProvinceForSociety = '{{ old("province") }}';
        const oldSocietyId = '{{ old("society_id") }}';
        if (societiesByProvince[oldProvinceForSociety]) {
            societiesByProvince[oldProvinceForSociety].forEach(function(society) {
                const option = document.createElement('option');
                option.value = society.id;
                option.textContent = society.name;
                if (String(society.id) === oldSocietyId) {
                    option.selected = true;
                }
                societySelect.appendChild(option);
            });
        }
    @endif

    provinceSelect.addEventListener('change', function() {
        const selectedProvince = this.value;

        // Clear and populate centers
        centerSelect.innerHTML = '<option value="" disabled selected>Choose center</option>';
        if (selectedProvince && centersMap[selectedProvince.toUpperCase()]) {
            centersMap[selectedProvince.toUpperCase()].forEach(function(center) {
                const option = document.createElement('option');
                option.value = center;
                option.textContent = center;
                centerSelect.appendChild(option);
            });
        }

        // Clear and populate societies (Phase 5B3: society_id)
        societySelect.innerHTML = '<option value="">Select Society</option>';
        if (selectedProvince && societiesByProvince[selectedProvince]) {
            societiesByProvince[selectedProvince].forEach(function(society) {
                const option = document.createElement('option');
                option.value = society.id;
                option.textContent = society.name;
                societySelect.appendChild(option);
            });
        }
    });
});
</script>
@endsection
