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
            <form action="{{ route('provincial.storeExecutor') }}" method="POST">
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
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
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
@endsection
