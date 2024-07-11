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
                            <label for="center">Center</label>
                            <input type="text" name="center" class="form-control" value="{{ $provincial->center }}">
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
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="province">Province</label>
                            <select name="province" class="form-control" required>
                                <option value="" disabled selected>Choose one</option>
                                <option value="Bangalore" {{ $provincial->province == 'Bangalore' ? 'selected' : '' }}>Bangalore</option>
                                <option value="Vijayawada" {{ $provincial->province == 'Vijayawada' ? 'selected' : '' }}>Vijayawada</option>
                                <option value="Visakhapatnam" {{ $provincial->province == 'Visakhapatnam' ? 'selected' : '' }}>Visakhapatnam</option>
                                <option value="Generalate" {{ $provincial->province == 'Generalate' ? 'selected' : '' }}>Generalate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" class="form-control">
                                <option value="" disabled selected>Choose one</option>
                                <option value="active" {{ $provincial->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $provincial->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
