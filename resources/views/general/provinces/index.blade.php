@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fp-text-center1">Province Management</h4>
                        <p class="mb-0 text-muted">Manage provinces and their provincial users</p>
                    </div>
                    <a href="{{ route('general.createProvince') }}" class="btn btn-primary">Create Province</a>
                </div>
                <div class="card-body">
                    {{-- Success/Error Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {!! session('success') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if($errors->has('delete_error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong> {{ $errors->first('delete_error') }}
                            @if($errors->has('users'))
                                <ul class="mt-2 mb-0">
                                    @foreach($errors->get('users') as $userList)
                                        @foreach($userList as $user)
                                            <li>{{ $user->name }} ({{ $user->email }}) - Role: {{ ucfirst($user->role) }}</li>
                                        @endforeach
                                    @endforeach
                                </ul>
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Search Form -->
                    <form method="GET" action="{{ route('general.provinces') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-10">
                                <input type="text" name="search" class="form-control" placeholder="Search by province name or provincial user name..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </div>
                        @if(request('search'))
                            <div class="mt-2">
                                <a href="{{ route('general.provinces') }}" class="btn btn-sm btn-secondary">Clear Search</a>
                            </div>
                        @endif
                    </form>

                    <!-- Summary Cards -->
                    <div class="mb-4 row">
                        <div class="col-md-4">
                            <div class="text-white card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Total Provinces</h5>
                                    <h3 class="card-text">{{ $provinces->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-white card bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Provinces with Provincial Users</h5>
                                    <h3 class="card-text">{{ $provinces->filter(fn($p) => $p['provincial_users']->count() > 0)->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-white card bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Total Users Across Provinces</h5>
                                    <h3 class="card-text">{{ $provinces->sum('user_count') }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($provinces->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Province Name</th>
                                    <th>Provincial Users</th>
                                    <th>Centers Count</th>
                                    <th>Users Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($provinces as $province)
                                <tr>
                                    <td>
                                        <strong>{{ $province['name'] }}</strong>
                                    </td>
                                    <td>
                                        @if($province['provincial_users']->count() > 0)
                                            @foreach($province['provincial_users'] as $user)
                                                <div class="mb-2">
                                                    <strong>{{ $user->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $user->email }}</small>
                                                    <br>
                                                    <span class="badge badge-success">
                                                        Provincial
                                                    </span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">No provincial users assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $province['center_count'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $province['user_count'] }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('general.editProvince', $province['name']) }}" class="btn btn-sm btn-info">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <p class="mb-0">No provinces found. <a href="{{ route('general.createProvince') }}">Create your first province</a> to get started.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
