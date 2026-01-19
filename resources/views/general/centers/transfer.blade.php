@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Transfer Center</h4>
                    <p class="mb-0 text-muted">Move center to another province</p>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Current Center Info -->
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading"><i data-feather="info"></i> Current Information</h6>
                        <p class="mb-1"><strong>Center:</strong> {{ $center->name }}</p>
                        <p class="mb-1"><strong>Current Province:</strong> {{ $center->province->name }}</p>
                        <p class="mb-0"><strong>Child Users:</strong> {{ $childUsersCount }} user(s) under your management</p>
                    </div>

                    @if($childUsersCount > 0)
                        <div class="alert alert-warning mb-4">
                            <i data-feather="alert-triangle"></i> <strong>Note:</strong> This center is used by {{ $childUsersCount }} user(s) under your management.
                            You can optionally update their province and center assignments when transferring.
                        </div>
                    @endif

                    <form action="{{ route('general.transferCenter', $center->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="target_province_id">Transfer To Province <span class="text-danger">*</span></label>
                            <select name="target_province_id" id="target_province_id" class="form-control" required>
                                <option value="" disabled selected>Select target province</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}" {{ old('target_province_id') == $province->id ? 'selected' : '' }}>
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select the province where you want to move this center.</small>
                        </div>

                        @if($childUsersCount > 0)
                            <div class="form-group mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="update_child_users" id="update_child_users" value="1" {{ old('update_child_users') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="update_child_users">
                                        Update province and center for child users ({{ $childUsersCount }} user(s))
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        If checked, all child users (including nested) with this center will have their province and center updated to match the new province.
                                    </small>
                                </div>
                            </div>
                        @endif

                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="arrow-right-circle"></i> Transfer Center
                            </button>
                            <a href="{{ route('general.manageCenters') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
