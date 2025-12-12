@extends('coordinator.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Create Provincial or Executor</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('coordinator.storeProvincial') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="province">Province</label>
                            <select name="province" class="form-control" required id="province">
                                <option value="" disabled selected>Choose one</option>
                                <option value="Bangalore">Bangalore</option>
                                <option value="Vijayawada">Vijayawada</option>
                                <option value="Visakhapatnam">Visakhapatnam</option>
                                <option value="Generalate">Generalate</option>
                                <option value="Divyodaya">Divyodaya</option>
                                <option value="Indonesia">Indonesia</option>
                                <option value="East Timor">East Timor</option>
                                <option value="East Africa">East Africa</option>
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
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" class="form-control" required>
                                <option value="" disabled selected>Choose one</option>
                                <option value="coordinator">Coordinator</option>
                                <option value="provincial">Provincial</option>
                                <option value="executor">Executor</option>
                                <option value="applicant">Applicant</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" class="form-control" required>
                                <option value="" disabled selected>Choose one</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Create</button>
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

    // Centers mapping
    const centersMap = @json($centersMap);

    provinceSelect.addEventListener('change', function() {
        const selectedProvince = this.value;
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
});
</script>
@endsection
