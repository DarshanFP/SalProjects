@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Register</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label for="name">Name</label>
            <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required autofocus>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" type="password" class="form-control" name="password" required>
        </div>

        <div class="form-group">
            <label for="password-confirm">Confirm Password</label>
            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" class="form-control" name="role" required>
                <option value="executor">Project Executor</option>
                <option value="manager">Project Manager</option>
                <option value="provincial">Provincial</option>
                <option value="coordinator">Project Coordinator</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <div class="form-group">
            <label for="parent_id">Parent User (optional)</label>
            <select id="parent_id" class="form-control" name="parent_id">
                <option value="">No Parent</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                Register
            </button>
        </div>
    </form>
</div>
@endsection
