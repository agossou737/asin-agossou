@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
    <div class="row row-cols-xxl-5 row-cols-md-3 row-cols-2 g-3 mb-3">
        <div class="col">
            <div class="card mb-0 h-100"><div class="card-body text-center py-3">
                <h2 class="mb-0">{{ $stats['total'] }}</h2>
                <p class="text-muted mb-0">Total des demandes</p>
            </div></div>
        </div>
        @foreach (\App\Enums\StatutDemande::cases() as $statut)
            <div class="col">
                <a href="{{ route('admin.demandes', ['statut' => $statut->value]) }}" class="text-decoration-none">
                    <div class="card mb-0 h-100"><div class="card-body text-center py-3">
                        <h2 class="mb-0 text-{{ $statut->couleur() }}">{{ $stats['par_statut'][$statut->value] }}</h2>
                        <p class="text-muted mb-0">{{ $statut->label() }}</p>
                    </div></div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header border-bottom border-dashed"><h4 class="header-title mb-0">Répartition par type d'acte</h4></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                            @foreach (\App\Enums\TypeActe::cases() as $type)
                                <tr>
                                    <td>{{ $type->label() }}</td>
                                    <td class="text-end fw-semibold">{{ $stats['par_type'][$type->value] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-inbox fs-48 text-primary d-block mb-2"></i>
                    <h4>{{ $stats['par_statut']['deposee'] + $stats['par_statut']['en_cours'] }} demande(s) à traiter</h4>
                    <a href="{{ route('admin.demandes', ['statut' => 'deposee']) }}" class="btn btn-primary mt-2">
                        <i class="ti ti-list-details me-1"></i> Traiter les demandes
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
