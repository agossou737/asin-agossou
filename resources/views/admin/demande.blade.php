@extends('layouts.app')

@section('title', 'Demande '.$demande->numero)
@section('page-title', 'Détails de la demande')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.demandes') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i> Retour à la liste
        </a>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h4 class="header-title mb-0"><i class="ti ti-file-text me-1"></i> {{ $demande->numero }}</h4>
                    <span class="badge text-bg-{{ $demande->statut->couleur() }} ms-auto px-2 py-1">{{ $demande->statut->label() }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                            <tr><th style="width:35%">Numéro de suivi</th><td class="fw-semibold">{{ $demande->numero }}</td></tr>
                            <tr><th>NPI</th><td>{{ $demande->npi }}</td></tr>
                            <tr><th>Email</th><td>{{ $demande->email ?? 'Non renseigné' }}</td></tr>
                            <tr><th>Type d'acte</th><td>{{ $demande->type_acte->label() }}</td></tr>
                            <tr><th>Nombre de copies</th><td>{{ $demande->nombre_copies }}</td></tr>
                            <tr><th>Déposée le</th><td>{{ $demande->created_at->format('d/m/Y à H:i') }}</td></tr>
                            <tr><th>Dernière mise à jour</th><td>{{ $demande->updated_at->format('d/m/Y à H:i') }}</td></tr>
                            @if ($demande->motif_rejet)
                                <tr><th>Motif du rejet</th><td class="text-danger">{{ $demande->motif_rejet }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom border-dashed"><h4 class="header-title mb-0"><i class="ti ti-history me-1"></i> Historique</h4></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Date</th><th>Changement</th><th>Par</th></tr></thead>
                            <tbody>
                                @foreach ($demande->historiques as $h)
                                    <tr>
                                        <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $h->ancien_statut?->label() ?? '—' }} &rarr; <strong>{{ $h->nouveau_statut->label() }}</strong>
                                            @if ($h->motif)<br><small class="text-danger">{{ $h->motif }}</small>@endif
                                        </td>
                                        <td>{{ $h->acteur?->name ?? ($h->source === 'depot' ? 'Usager' : ($h->source === 'api' ? 'API (jeton)' : 'Inconnu')) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card" id="carte-actions" data-id="{{ $demande->id }}">
                <div class="card-header border-bottom border-dashed"><h4 class="header-title mb-0"><i class="ti ti-adjustments me-1"></i> Traitement</h4></div>
                <div class="card-body">
                    @forelse ($demande->statut->transitionsPossibles() as $cible)
                        @php
                            $a = match ($cible) {
                                \App\Enums\StatutDemande::EnCours => ['Prendre en charge', 'player-play', 'warning'],
                                \App\Enums\StatutDemande::Validee => ['Valider la demande', 'check', 'success'],
                                \App\Enums\StatutDemande::Rejetee => ['Rejeter la demande', 'x', 'danger'],
                            };
                        @endphp
                        <div class="d-grid mb-2">
                            <button type="button" class="btn btn-{{ $a[2] }}" data-action="{{ $cible->value }}">
                                <i class="ti ti-{{ $a[1] }} me-1"></i> {{ $a[0] }}
                            </button>
                        </div>
                    @empty
                        <div class="alert alert-secondary mb-0">
                            <i class="ti ti-lock me-1"></i> Traitement terminé : cette demande est
                            <strong>{{ mb_strtolower($demande->statut->label()) }}</strong> et ne peut plus changer.
                        </div>
                    @endforelse

                    @if ($demande->statut === \App\Enums\StatutDemande::Validee)
                        <div class="d-grid mt-3">
                            <a href="{{ route('admin.demandes.pdf', $demande->id) }}" class="btn btn-outline-success">
                                <i class="ti ti-file-download me-1"></i> Télécharger le PDF
                            </a>
                        </div>
                    @endif

                    @if (! $demande->statut->estFinal())
                        <p class="text-muted fs-13 mt-3 mb-0">
                            Une validation ou un rejet est définitif et notifie l'usager par email (s'il en a renseigné un).
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/admin-demande.js') }}"></script>
@endpush
