<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | ASIN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <link href="{{ asset('assets/css/vendor.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/demandes.css') }}" rel="stylesheet" type="text/css" />
</head>

<body>
    <div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="col-xl-4 col-lg-5 col-md-7 col-sm-10">
            <div class="text-center mb-4">
                <img src="{{ asset('assets/images/logo-dark.png') }}" alt="logo" height="40">
            </div>
            @yield('content')
            <p class="text-center text-muted mt-3 mb-0 fs-13">&copy; {{ date('Y') }} Étude de cas DEP/ASIN</p>
        </div>
    </div>

    <script>
        window.APP = {
            apiUrl: @json(url('/api')),
            adminApiUrl: @json(url('/admin/api')),
            admin: false,
            urls_admin: { login: @json(route('admin.login')) },
        };
    </script>
    <script src="{{ asset('assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/js/demandes-common.js') }}"></script>
    @stack('scripts')
</body>

</html>
