<div class="sidenav-menu">

    <a href="{{ route('demandes.nouvelle') }}" class="logo">
        <span class="logo-light">
            <span class="logo-lg"><img src="{{ asset('assets/images/logo.png') }}" alt="logo"></span>
            <span class="logo-sm text-center"><img src="{{ asset('assets/images/logo-sm.png') }}" alt="logo"></span>
        </span>
        <span class="logo-dark">
            <span class="logo-lg"><img src="{{ asset('assets/images/logo-dark.png') }}" alt="logo"></span>
            <span class="logo-sm text-center"><img src="{{ asset('assets/images/logo-sm.png') }}" alt="logo"></span>
        </span>
    </a>

    <button class="button-sm-hover">
        <i class="ti ti-circle align-middle"></i>
    </button>

    <button class="button-close-fullsidebar">
        <i class="ti ti-x align-middle"></i>
    </button>

    <div data-simplebar>
        <ul class="side-nav">

            @if (request()->routeIs('admin.*'))
                <li class="side-nav-title mt-2">Administration</li>

                <li class="side-nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="side-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                        <span class="menu-text"> Tableau de bord </span>
                    </a>
                </li>

                <li class="side-nav-title mt-2">Demandes</li>

                <li class="side-nav-item">
                    <a href="{{ route('admin.demandes') }}" class="side-nav-link {{ request()->routeIs('admin.demandes') && ! request('statut') ? 'active' : '' }}">
                        <span class="menu-icon"><i class="ti ti-list-details"></i></span>
                        <span class="menu-text"> Toutes les demandes </span>
                    </a>
                </li>
                @foreach (\App\Enums\StatutDemande::cases() as $statut)
                    <li class="side-nav-item">
                        <a href="{{ route('admin.demandes', ['statut' => $statut->value]) }}" class="side-nav-link {{ request()->routeIs('admin.demandes') && request('statut') === $statut->value ? 'active' : '' }}">
                            <span class="menu-icon"><i class="ti ti-circle-filled text-{{ $statut->couleur() }}"></i></span>
                            <span class="menu-text"> {{ $statut->label() }} </span>
                        </a>
                    </li>
                @endforeach
            @else
                <li class="side-nav-title mt-2">Demandes d'actes</li>

                <li class="side-nav-item">
                    <a href="{{ route('demandes.nouvelle') }}" class="side-nav-link {{ request()->routeIs('demandes.nouvelle') ? 'active' : '' }}">
                        <span class="menu-icon"><i class="ti ti-file-plus"></i></span>
                        <span class="menu-text"> Déposer une demande </span>
                    </a>
                </li>
                <li class="side-nav-item">
                    <a href="{{ route('demandes.index') }}" class="side-nav-link {{ request()->routeIs('demandes.index') ? 'active' : '' }}">
                        <span class="menu-icon"><i class="ti ti-list-search"></i></span>
                        <span class="menu-text"> Suivre ma demande </span>
                    </a>
                </li>
            @endif

        </ul>
    </div>
</div>
