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

                    <h6 class="card-title">Update Information</h6>

                    <form method="POST" action="{{ route('profile.update') }}" class="forms-sample" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="exampleInputUsername1" class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" id="exampleInputUsername1" autocomplete="off" value="{{ $profileData->name }}">
                        </div>
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" id="exampleInputUsername1" autocomplete="off" value="{{ $profileData->email }}">
                        </div>
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" id="exampleInputUsername1" autocomplete="off" value="{{ $profileData->phone }}">
                        </div>
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="form-label">Center</label>
                            <input type="text" name="center" class="form-control" id="exampleInputUsername1" autocomplete="off" value="{{ $profileData->center }}">
                        </div>
                        <button type="submit" class="btn btn-primary me-2">Save</button>
                    </form>
                </div>
            </div>
         </div>
    </div>
      <!-- middle wrapper end -->
      <!-- right wrapper start -->

      <!-- right wrapper end -->
    </div>

</div>
@endsection
