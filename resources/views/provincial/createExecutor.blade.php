@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Create New Executor</h4>
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <form action="{{ route('provincial.storeExecutor') }}" method="POST" id="executorForm">
                @csrf
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <span toggle="#password" class="fa fa-fw fa-eye field-icon toggle-password"></span>
                    <small id="passwordHelp" class="form-text text-muted"></small>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    <span toggle="#password_confirmation" class="fa fa-fw fa-eye field-icon toggle-password"></span>
                    <small id="confirmPasswordHelp" class="form-text"></small>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="society_name">Society Name</label>
                    <input type="text" class="form-control" id="society_name" name="society_name" required>
                </div>
                <div class="form-group">
                    <label for="center">Center</label>
                    <select class="form-control" id="center" name="center" required>
                        <option value="" disabled selected>Select Center</option>
                        @foreach($centers as $center)
                            <option value="{{ $center }}">{{ $center }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Member</button>
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
    const togglePasswords = document.querySelectorAll('.toggle-password');

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
