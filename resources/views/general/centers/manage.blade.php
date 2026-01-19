@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fp-text-center1">Center Management</h4>
                        <p class="mb-0 text-muted">Transfer centers between provinces and manage center assignments</p>
                    </div>
                    <a href="{{ route('general.manageUserCenters') }}" class="btn btn-secondary">Manage User Centers</a>
                </div>
                <div class="card-body">
                    {{-- Success/Error Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {!! session('success') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Provinces and Centers List -->
                    @foreach($provinces as $province)
                        <div class="mb-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i data-feather="map-pin"></i> {{ $province->name }}
                                        @if($province->coordinator)
                                            <span class="badge bg-success ms-2">Has Coordinator</span>
                                        @endif
                                        <span class="badge bg-primary ms-2">{{ $province->activeCenters->count() }} Centers</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($province->activeCenters->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Center Name</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($province->activeCenters as $center)
                                                        <tr>
                                                            <td>{{ $center->name }}</td>
                                                            <td>
                                                                @if($center->is_active)
                                                                    <span class="badge bg-success">Active</span>
                                                                @else
                                                                    <span class="badge bg-secondary">Inactive</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('general.showTransferCenter', $center->id) }}" class="btn btn-sm btn-primary">
                                                                    <i data-feather="arrow-right-circle"></i> Transfer
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">No centers in this province.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if($provinces->count() == 0)
                        <div class="alert alert-info">
                            <i data-feather="info"></i> No provinces found. <a href="{{ route('general.createProvince') }}">Create a province</a> first.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
