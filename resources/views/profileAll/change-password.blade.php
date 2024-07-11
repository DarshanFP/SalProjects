@extends('layoutAll.app')
@section('admin')

<div class="page-content">
    <div class="row profile-body">
        <!-- left wrapper start -->
        <div class="d-none d-md-block col-md-4 col-xl-4 left-wrapper">
            <div class="rounded card">
                <div class="card-body">
                    <div class="mb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="h4 ms-3">{{ $profileData->name }}</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="mb-0 tx-11 fw-bolder text-uppercase">Username:</label>
                        <p class="text-muted">{{ $profileData->username }}</p>
                    </div>
                    <div class="mt-3">
                        <label class="mb-0 tx-11 fw-bolder text-uppercase">Email:</label>
                        <p class="text-muted">{{ $profileData->email }}</p>
                    </div>
                    <div class="mt-3">
                        <label class="mb-0 tx-11 fw-bolder text-uppercase">Phone:</label>
                        <p class="text-muted">{{ $profileData->phone }}</p>
                    </div>
                    <div class="mt-3">
                        <label class="mb-0 tx-11 fw-bolder text-uppercase">Center:</label>
                        <p class="text-muted">{{ $profileData->center }}</p>
                    </div>
                    <div class="mt-3">
                        <label class="mb-0 tx-11 fw-bolder text-uppercase">Role:</label>
                        <p class="text-muted">{{ $profileData->role }}</p>
                    </div>
                    <div class="mt-3">
                        <label class="mb-0 tx-11 fw-bolder text-uppercase">Status:</label>
                        <p class="text-muted">{{ $profileData->status }}</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- left wrapper end -->
        <!-- middle wrapper start -->
        <div class="col-md-8 col-xl-8 middle-wrapper">
            <div class="row">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Change Password</h6>
                        <form method="POST" action="{{ route('profile.update-password') }}" class="forms-sample" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="old_password" class="form-label">Old Password</label>
                                <input type="password" name="old_password" class="form-control @error('old_password') is-invalid @enderror" id="old_password" autocomplete="off">
                                @error('old_password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror" id="new_password" autocomplete="off">
                                @error('new_password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" name="new_password_confirmation" class="form-control" id="confirm_password" autocomplete="off">
                            </div>
                            <button type="submit" class="btn btn-primary me-2">Save new Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- middle wrapper end -->
    </div>
</div>
@endsection
