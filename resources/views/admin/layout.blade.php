<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Admin — SalProjects (read-only visibility)">
    <meta name="author" content="NobleUI">

    <title>@yield('title', 'Admin') - SalProjects</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/core/core.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/fonts/feather-font/css/iconfont.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/css/demo2/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom/project-forms.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom/textarea-auto-resize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom/common-tables.css') }}">
    <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.png') }}" />
</head>
<body>
    <div class="main-wrapper">
        @include('admin.sidebar')

        <div class="page-wrapper">
            @include('admin.partials.impersonation-banner')
            @include('layoutAll.header')
            @yield('content')
            @include('layoutAll.footer')
        </div>
    </div>

    <script src="{{ asset('backend/assets/vendors/core/core.js') }}"></script>
    <script src="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('backend/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('backend/assets/vendors/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('backend/assets/js/template.js') }}"></script>
    <script src="{{ asset('backend/assets/js/dashboard-dark.js') }}"></script>
    <script src="{{ asset('js/textarea-auto-resize.js') }}"></script>
    @stack('scripts')
</body>
</html>
