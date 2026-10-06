<header class="app-topbar">
    <div class="page-container topbar-menu">
        <div class="d-flex align-items-center gap-2">

            <a href="{{ route('demandes.nouvelle') }}" class="logo">
                <span class="logo-light">
                    <span class="logo-lg"><img src="{{ asset('assets/images/logo.png') }}" alt="logo"></span>
                    <span class="logo-sm"><img src="{{ asset('assets/images/logo-sm.png') }}" alt="logo"></span>
                </span>
                <span class="logo-dark">
                    <span class="logo-lg"><img src="{{ asset('assets/images/logo-dark.png') }}" alt="logo"></span>
                    <span class="logo-sm"><img src="{{ asset('assets/images/logo-sm.png') }}" alt="logo"></span>
                </span>
            </a>

            <button class="sidenav-toggle-button btn btn-secondary btn-icon">
                <i class="ti ti-menu-deep fs-24"></i>
            </button>

            <span class="d-none d-md-inline fw-semibold text-muted ms-2">Suivi des demandes d'actes administratifs</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if (request()->routeIs('admin.*') && auth()->check())
                <span class="d-none d-md-inline text-muted"><i class="ti ti-user-shield me-1"></i>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="ti ti-logout me-1"></i> Déconnexion</button>
                </form>
            @endif
            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link btn btn-outline-primary btn-icon" id="light-dark-mode" type="button" title="Mode clair / sombre">
                    <i class="ti ti-moon fs-22"></i>
                </button>
            </div>
        </div>
    </div>
</header>
