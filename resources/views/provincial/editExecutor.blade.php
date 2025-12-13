@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Edit Executor</h4>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <form action="{{ route('provincial.updateExecutor', $executor->id) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $executor->name }}" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="{{ $executor->username }}" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ $executor->email }}" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="{{ $executor->phone }}">
                </div>
                <div class="form-group">
                    <label for="society_name">Society Name</label>
                    <select class="form-control" id="society_name" name="society_name" required>
                        <option value="" disabled>Select Society / Trust</option>
                        <option value="ST. ANN'S EDUCATIONAL SOCIETY" {{ $executor->society_name == "ST. ANN'S EDUCATIONAL SOCIETY" ? 'selected' : '' }}>ST. ANN'S EDUCATIONAL SOCIETY</option>
                        <option value="SARVAJANA SNEHA CHARITABLE TRUST" {{ $executor->society_name == "SARVAJANA SNEHA CHARITABLE TRUST" ? 'selected' : '' }}>SARVAJANA SNEHA CHARITABLE TRUST</option>
                        <option value="WILHELM MEYERS DEVELOPMENTAL SOCIETY" {{ $executor->society_name == "WILHELM MEYERS DEVELOPMENTAL SOCIETY" ? 'selected' : '' }}>WILHELM MEYERS DEVELOPMENTAL SOCIETY</option>
                        <option value="ST. ANN'S SOCIETY, VISAKHAPATNAM" {{ $executor->society_name == "ST. ANN'S SOCIETY, VISAKHAPATNAM" ? 'selected' : '' }}>ST. ANN'S SOCIETY, VISAKHAPATNAM</option>
                        <option value="ST.ANN'S SOCIETY, SOUTHERN REGION" {{ $executor->society_name == "ST.ANN'S SOCIETY, SOUTHERN REGION" ? 'selected' : '' }}>ST.ANN'S SOCIETY, SOUTHERN REGION</option>
                        <option value="ST. ANNE'S SOCIETY" {{ $executor->society_name == "ST. ANNE'S SOCIETY" ? 'selected' : '' }}>ST. ANNE'S SOCIETY</option>
                        <option value="BIARA SANTA ANNA, MAUSAMBI" {{ $executor->society_name == "BIARA SANTA ANNA, MAUSAMBI" ? 'selected' : '' }}>BIARA SANTA ANNA, MAUSAMBI</option>
                        <option value="ST. ANN'S CONVENT, LURO" {{ $executor->society_name == "ST. ANN'S CONVENT, LURO" ? 'selected' : '' }}>ST. ANN'S CONVENT, LURO</option>
                        <option value="MISSIONARY SISTERS OF ST. ANN" {{ $executor->society_name == "MISSIONARY SISTERS OF ST. ANN" ? 'selected' : '' }}>MISSIONARY SISTERS OF ST. ANN</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="center">Center</label>
                    <select class="form-control" id="center" name="center" required>
                        <option value="" disabled>Select Center</option>
                        @foreach($centers as $center)
                            <option value="{{ $center }}" {{ $executor->center == $center ? 'selected' : '' }}>{{ $center }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" class="form-control" id="address" name="address" value="{{ $executor->address }}">
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="executor" {{ $executor->role == 'executor' ? 'selected' : '' }}>Executor</option>
                        <option value="applicant" {{ $executor->role == 'applicant' ? 'selected' : '' }}>Applicant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="active" {{ $executor->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $executor->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>

            <h4 class="mt-4 card-title">Reset Password</h4>
            <form action="{{ route('provincial.resetExecutorPassword', $executor->id) }}" method="POST" id="resetPasswordForm">
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
@endsection
