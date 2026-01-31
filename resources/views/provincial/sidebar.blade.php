{{-- Provincial sidebar: uses shared sidebar architecture. Order and content are defined in partials/sidebar/provincial.blade.php. --}}
@include('layouts.sidebar', [
    'role' => 'provincial',
    'dashboardRoute' => route('provincial.dashboard'),
])
