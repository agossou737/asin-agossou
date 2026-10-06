@extends('layouts.app')

@section('title', 'Déposer une demande')
@section('page-title', 'Déposer une demande')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-8">
            <div class="card">
                <div class="card-header border-bottom border-dashed d-flex align-items-center">
                    <h4 class="header-title mb-0"><i class="ti ti-file-plus me-1"></i> Nouvelle demande d'acte</h4>
                </div>

                <div class="card-body">
                    <p class="text-muted">
                        Renseignez votre NPI, le type d'acte souhaité et le nombre de copies.
                        Une seule demande est possible par type d'acte et par NPI. Votre demande sera enregistrée avec le statut <span class="badge text-bg-secondary ms-0">Déposée</span>.
                    </p>

                    <form id="form-demande" novalidate>
                        <div class="mb-3">
                            <label for="npi" class="form-label">NPI <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="npi" name="npi" inputmode="numeric"
                                   maxlength="10" placeholder="10 chiffres, ex. 0123456789" autocomplete="off" autofocus>
                            <div class="form-text">Numéro personnel d'identification : exactement 10 chiffres.</div>
                            <div class="invalid-feedback" data-error-for="npi"></div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse email <span class="text-muted">(facultatif)</span></label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="255"
                                   placeholder="vous@exemple.com" autocomplete="email">
                            <div class="form-text">Si vous la renseignez, vous recevrez votre numéro de suivi, puis la décision (validation ou rejet).</div>
                            <div class="invalid-feedback" data-error-for="email"></div>
                        </div>

                        <div class="mb-3">
                            <label for="type_acte" class="form-label">Type d'acte <span class="text-danger">*</span></label>
                            <select class="form-select" id="type_acte" name="type_acte">
                                <option value="">-- Choisir un type d'acte --</option>
                                @foreach (\App\Enums\TypeActe::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="type_acte"></div>
                        </div>

                        <div class="mb-4">
                            <label for="nombre_copies" class="form-label">Nombre de copies <span class="text-danger">*</span></label>
                            <select class="form-select" id="nombre_copies" name="nombre_copies">
                                @foreach (range(1, 5) as $n)
                                    <option value="{{ $n }}">{{ $n }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Entre 1 et 5 copies.</div>
                            <div class="invalid-feedback" data-error-for="nombre_copies"></div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="reset" class="btn btn-outline-secondary">Réinitialiser</button>
                            <button type="submit" class="btn btn-primary" id="btn-deposer">
                                <i class="ti ti-send me-1"></i> Déposer la demande
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/demandes-nouvelle.js') }}"></script>
@endpush
