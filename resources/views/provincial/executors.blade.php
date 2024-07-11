<!-- resources/views/provincial/executors.blade.php -->
@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">List of Executors</h4>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($executors->isEmpty())
                <p>No executors found under your supervision.</p>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
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
                                    <td>{{ $executor->center }}</td>
                                    <td>{{ $executor->status }}</td>
                                    <td>
                                        <a href="{{ route('provincial.editExecutor', $executor->id) }}" class="btn btn-sm btn-primary">Edit</a>
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
