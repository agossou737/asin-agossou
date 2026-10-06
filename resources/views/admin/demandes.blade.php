@extends('layouts.app')

@section('title', 'Toutes les demandes')
@section('page-title', 'Les demandes')

@section('content')
    {{-- Acces rapide par statut --}}
    <div class="d-flex flex-wrap gap-2 mb-3" id="boutons-statut">
        <button type="button" class="btn btn-outline-dark" data-statut="">
            <i class="ti ti-list-details me-1"></i> Toutes <span class="badge text-bg-dark ms-1" id="n-total">0</span>
        </button>
        @foreach (\App\Enums\StatutDemande::cases() as $statut)
            <button type="button" class="btn btn-outline-{{ $statut->couleur() }}" data-statut="{{ $statut->value }}">
                {{ $statut->label() }} <span class="badge text-bg-{{ $statut->couleur() }} ms-1" id="n-{{ $statut->value }}">0</span>
            </button>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <form id="form-filtres" class="row g-3 align-items-end" novalidate>
                <div class="col-lg-4 col-md-6">
                    <label for="f-numero" class="form-label">Numéro de demande</label>
                    <input type="text" class="form-control" id="f-numero" maxlength="20" placeholder="DEM-20261006-…" autocomplete="off">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="f-npi" class="form-label">NPI (début ou complet)</label>
                    <input type="text" class="form-control" id="f-npi" inputmode="numeric" maxlength="10" autocomplete="off">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="f-type" class="form-label">Type d'acte</label>
                    <select class="form-select" id="f-type">
                        <option value="">Tous</option>
                        @foreach (\App\Enums\TypeActe::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i> Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom border-dashed d-flex align-items-center">
            <h4 class="header-title mb-0" id="titre-liste">Toutes les demandes</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Numéro</th><th>NPI</th><th>Email</th><th>Type d'acte</th>
                            <th class="text-center">Copies</th><th>Statut</th><th>Déposée le</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="liste-demandes">
                        <tr><td colspan="8" class="text-center text-muted py-5">Chargement…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-none flex-wrap align-items-center gap-2" id="pagination-bar">
            <span class="text-muted" id="pagination-info"></span>
            <ul class="pagination pagination-sm mb-0 ms-auto" id="pagination"></ul>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/admin-demandes.js') }}"></script>
@endpush
