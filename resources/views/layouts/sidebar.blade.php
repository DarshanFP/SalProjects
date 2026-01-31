{{--
    Shared sidebar architecture.
    Renders: container, header (brand + toggler), scrollable body, role-specific nav content.
    Variables: $role (required), $dashboardRoute (required, URL for brand link).
--}}
<nav class="sidebar">
    <div class="sidebar-header">
        <a href="{{ $dashboardRoute ?? '#' }}" class="sidebar-brand">
            SAL <span>Projects</span>
        </a>
        <div class="sidebar-toggler not-active">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    <div class="sidebar-body sidebar-body--scrollable">
        <ul class="nav">
            @include('partials.sidebar.' . $role)
        </ul>
    </div>
</nav>
