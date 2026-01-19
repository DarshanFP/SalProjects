@extends('general.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Edit Province: {{ $provinceName }}</h4>
                    <p class="mb-0 text-muted">Update province details</p>
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

                    <!-- Current Province Info -->
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading"><i data-feather="info"></i> Current Information</h6>
                        <p class="mb-1"><strong>Province:</strong> {{ $provinceName }}</p>
                        <p class="mb-1"><strong>Provincial Users:</strong>
                            @if($provincialUsers->count() > 0)
                                @foreach($provincialUsers as $user)
                                    {{ $user->name }} ({{ $user->email }})@if(!$loop->last), @endif
                                @endforeach
                            @else
                                None assigned
                            @endif
                        </p>
                        <p class="mb-0"><strong>Users in Province:</strong> {{ $userCount }}</p>
                    </div>

                    @if($userCount > 0)
                        <div class="alert alert-warning">
                            <i data-feather="alert-triangle"></i> <strong>Warning:</strong> This province has {{ $userCount }} user(s) assigned. Changing the province name will update all users' province assignment.
                        </div>
                    @endif

                    <form action="{{ route('general.updateProvince', $provinceName) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="name">Province Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $provinceName) }}" required>
                            <small class="form-text text-muted">If you change the name, all users assigned to this province will be updated.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="provincial_user_ids">Provincial Users</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                @if($eligibleUsers->count() > 0)
                                    @foreach($eligibleUsers as $user)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   name="provincial_user_ids[]"
                                                   value="{{ $user['id'] }}"
                                                   id="user_{{ $user['id'] }}"
                                                   {{ $user['is_assigned_to_this_province'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="user_{{ $user['id'] }}">
                                                <strong>{{ $user['name'] }}</strong>
                                                ({{ $user['email'] }})
                                                <span class="badge badge-{{ $user['role'] === 'general' ? 'info' : 'success' }}">
                                                    {{ ucfirst($user['role']) }}
                                                </span>
                                                @if($user['current_province_id'] && !$user['is_assigned_to_this_province'] && $user['role'] !== 'general')
                                                    <small class="text-muted">(Currently assigned to another province)</small>
                                                @elseif($user['role'] === 'general' && $user['current_province_id'] && !$user['is_assigned_to_this_province'])
                                                    <small class="text-info">(General user - can manage multiple provinces)</small>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted mb-0">No eligible users found. Users must have role 'provincial' or 'general', or not be assigned to any province.</p>
                                @endif
                            </div>
                            <small class="form-text text-muted">
                                Select users to assign as provincial users for this province.
                                Users with role 'provincial' or 'general' can be assigned.
                                General users can be provincial for multiple provinces.
                            </small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="centers">Centers</label>
                            <textarea name="centers" id="centers" class="form-control auto-resize-textarea" rows="8" placeholder="Enter centers one per line">{{ old('centers', $centersString) }}</textarea>
                            <small class="form-text text-muted">Enter centers one per line. Adding a new center name will create it. Removing a center name will deactivate it. You can edit existing center names by modifying them in the textarea.</small>
                        </div>
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save"></i> Update Province
                            </button>
                            <a href="{{ route('general.provinces') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endpush
@endsection
