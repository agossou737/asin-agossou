@extends('layouts.auth')

@section('title', 'Connexion administrateur')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <h4 class="fw-bold text-uppercase mb-1"><i class="ti ti-lock me-1"></i> Espace administrateur</h4>
            <p class="text-muted">Accès réservé aux agents habilités.</p>

            <form id="form-login" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="email" class="form-control" id="email" name="email" autocomplete="username" maxlength="255" autofocus>
                    <div class="invalid-feedback" data-error-for="email"></div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" maxlength="255">
                    <div class="invalid-feedback" data-error-for="password"></div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary" id="btn-login"><i class="ti ti-login me-1"></i> Se connecter</button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('demandes.nouvelle') }}" class="text-muted"><i class="ti ti-arrow-left me-1"></i> Retour au site</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/admin-login.js') }}"></script>
@endpush
