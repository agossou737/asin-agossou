<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <title>@yield('title', "Suivi des demandes d'actes") | ASIN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="{{ request()->routeIs('admin.*') ? 'noindex, nofollow' : 'index, follow' }}">
    <meta name="description" content="Suivi des demandes d'actes administratifs - Étude de cas ASIN">

    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Theme config (doit etre charge en premier) -->
    <script src="{{ asset('assets/js/config.js') }}"></script>

    <link href="{{ asset('assets/css/vendor.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/demandes.css') }}" rel="stylesheet" type="text/css" />

    @stack('styles')
</head>

<body>
    <div class="wrapper">

        @include('layouts.partials.sidebar')
        @include('layouts.partials.topbar')

        <div class="page-content">
            <div class="page-container">

                <div class="row">
                    <div class="col-12">
                        <div class="page-title-head d-flex align-items-sm-center flex-sm-row flex-column">
                            <div class="flex-grow-1">
                                <h4 class="fs-18 text-uppercase fw-bold m-0">@yield('page-title')</h4>
                            </div>
                            <div class="mt-3 mt-sm-0">
                                <ol class="breadcrumb m-0 py-0">
                                    <li class="breadcrumb-item"><a href="{{ request()->routeIs('admin.*') ? route('admin.dashboard') : route('demandes.nouvelle') }}">{{ request()->routeIs('admin.*') ? 'Administration' : "Demandes d'actes" }}</a></li>
                                    <li class="breadcrumb-item active">@yield('page-title')</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                @yield('content')

            </div>

            @include('layouts.partials.footer')
        </div>
    </div>

    <script>
        // Configuration partagee avec le JavaScript de l'application.
        window.APP = {
            apiUrl: @json(url('/api')),
            admin: @json(request()->routeIs('admin.*')),
            urls: {
                nouvelle: @json(route('demandes.nouvelle')),
                liste: @json(route('demandes.index')),
            },
            @if (request()->routeIs('admin.*'))
            adminApiUrl: @json(url('/admin/api')),
            urls_admin: {
                login: @json(route('admin.login')),
                demandes: @json(route('admin.demandes')),
            },
            @endif
        };
    </script>

    <script src="{{ asset('assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/js/demandes-common.js') }}"></script>

    @stack('scripts')
</body>

</html>
