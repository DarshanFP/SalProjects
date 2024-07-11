@extends('coordinator.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">All Provincials</h4>
                    <a href="{{ route('coordinator.createProvincial') }}" class="float-right btn btn-primary">Create Provincial</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Province</th>
                                    <th>Role</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Center</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($provincials as $provincial)
                                <tr>
                                    <td>{{ $provincial->province }}</td>
                                    <td>{{ $provincial->role }}</td>
                                    <td>{{ $provincial->name }}</td>
                                    <td>{{ $provincial->username }}</td>
                                    <td>{{ $provincial->email }}</td>
                                    <td>{{ $provincial->phone }}</td>
                                    <td>{{ $provincial->center }}</td>
                                    <td>{{ $provincial->address }}</td>
                                    <td>{{ $provincial->status }}</td>
                                    <td>
                                        <a href="{{ route('coordinator.editProvincial', $provincial->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                        <form action="{{ route('coordinator.resetProvincialPassword', $provincial->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm">Reset Password</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
