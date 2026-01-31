{{-- Phase 4: Placeholder. Shown when session('impersonation') is set (not implemented yet). --}}
@if(config('admin.impersonation_enabled', false) && session()->has('impersonation'))
<div class="alert alert-warning alert-dismissible fade show rounded-0 mb-0" role="alert">
    <strong>Acting as:</strong> {{ session('impersonation.impersonated_user_name', 'Unknown') }} ({{ session('impersonation.effective_role', '—') }}).
    <strong>Logged in as Admin:</strong> {{ auth()->user()->name ?? 'Admin' }}.
    <form action="{{ url('/admin/impersonate/stop') }}" method="POST" class="d-inline ms-2">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-dark">Exit impersonation</button>
    </form>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
