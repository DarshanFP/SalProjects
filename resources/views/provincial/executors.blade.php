<!-- resources/views/provincial/executors.blade.php -->
@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">List of Users (Executors & Applicants)</h4>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($executors->isEmpty())
                <p>No users found under your supervision.</p>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Center</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($executors as $executor)
                                <tr>
                                    <td>{{ $executor->name }}</td>
                                    <td>{{ $executor->username }}</td>
                                    <td>{{ $executor->email }}</td>
                                    <td>{{ $executor->phone }}</td>
                                    <td>{{ ucfirst($executor->role) }}</td>
                                    <td>{{ $executor->center }}</td>
                                    <td>
                                        @if($executor->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('provincial.editExecutor', $executor->id) }}" class="btn btn-sm btn-primary">Edit</a>
                                        @if($executor->status === 'active')
                                            <form action="{{ route('provincial.deactivateUser', $executor->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to deactivate this user?')">Deactivate</button>
                                            </form>
                                        @else
                                            <form action="{{ route('provincial.activateUser', $executor->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to activate this user?')">Activate</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
