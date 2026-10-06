@extends('layouts.app')

@section('title', 'Suivre ma demande')
@section('page-title', 'Suivre ma demande')

@section('content')
    {{-- Recherche par NPI + filtre par statut --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="form-recherche" class="row g-3 align-items-end" novalidate>
                        <div class="col-lg-5 col-md-6">
                            <label for="recherche" class="form-label">NPI ou numéro de demande <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="recherche" maxlength="20"
                                   placeholder="NPI (10 chiffres) ou DEM-20261006-ABC123" autocomplete="off">
                            <div class="form-text">Le numéro de demande vous a été remis au dépôt (et envoyé par email si vous l'avez renseigné).</div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label for="filtre-statut" class="form-label">Statut <span class="text-muted">(recherche par NPI)</span></label>
                            <select class="form-select" id="filtre-statut">
                                <option value="">Tous les statuts</option>
                                @foreach (\App\Enums\StatutDemande::cases() as $statut)
                                    <option value="{{ $statut->value }}">{{ $statut->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-12 d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-search me-1"></i> Rechercher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Compteurs par statut (bonus) --}}
    <div class="row row-cols-xxl-5 row-cols-md-3 row-cols-2 g-3 mb-3 d-none" id="compteurs">
        <div class="col">
            <div class="card mb-0 h-100"><div class="card-body text-center py-3">
                <h3 class="mb-0" id="cpt-total">0</h3>
                <p class="text-muted mb-0">Total</p>
            </div></div>
        </div>
        @foreach (\App\Enums\StatutDemande::cases() as $statut)
            <div class="col">
                <div class="card mb-0 h-100"><div class="card-body text-center py-3">
                    <h3 class="mb-0 text-{{ $statut->couleur() }}" id="cpt-{{ $statut->value }}">0</h3>
                    <p class="text-muted mb-0">{{ $statut->label() }}</p>
                </div></div>
            </div>
        @endforeach
    </div>

    {{-- Liste --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h4 class="header-title mb-0">Demandes de l'usager</h4>
                    <a href="{{ route('demandes.nouvelle') }}" class="btn btn-sm btn-outline-primary ms-auto">
                        <i class="ti ti-plus me-1"></i> Nouvelle demande
                    </a>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Type d'acte</th>
                                    <th class="text-center">Copies</th>
                                    <th>Statut</th>
                                    <th>Déposée le</th>
                                    <th class="text-end">Document</th>
                                </tr>
                            </thead>
                            <tbody id="liste-demandes">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="ti ti-search fs-32 d-block mb-2"></i>
                                        Saisissez un NPI pour afficher les demandes de l'usager.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-none flex-wrap align-items-center gap-2" id="pagination-bar">
                    <span class="text-muted" id="pagination-info"></span>
                    <ul class="pagination pagination-sm mb-0 ms-auto" id="pagination"></ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/demandes-index.js') }}"></script>
@endpush
