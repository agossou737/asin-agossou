<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Demande {{ $demande->numero }}</title>
    <style>
        @page { margin: 40px 45px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #222; }
        .entete { border-bottom: 3px solid #1f3a5f; padding-bottom: 10px; margin-bottom: 22px; }
        .entete h1 { font-size: 20px; color: #1f3a5f; margin: 0 0 4px 0; }
        .entete p { margin: 0; color: #666; font-size: 11px; }
        .statut { display: inline-block; padding: 5px 14px; background: #198754; color: #fff; font-weight: bold; border-radius: 4px; }
        h2 { font-size: 13px; color: #1f3a5f; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin: 24px 0 8px 0; }
        table.details { width: 100%; border-collapse: collapse; }
        table.details th { text-align: left; width: 38%; background: #f1f3f6; padding: 8px 10px; border: 1px solid #d5d9e0; }
        table.details td { padding: 8px 10px; border: 1px solid #d5d9e0; }
        table.historique { width: 100%; border-collapse: collapse; font-size: 11px; }
        table.historique th { background: #1f3a5f; color: #fff; text-align: left; padding: 6px 8px; }
        table.historique td { padding: 6px 8px; border-bottom: 1px solid #e3e6ea; }
        .numero { font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .pied { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <div class="entete">
        <h1>Demande d'acte validée</h1>
        <p>Récapitulatif de la demande &middot; document généré le {{ $genereLe->format('d/m/Y à H:i') }}</p>
    </div>

    <p><span class="statut">VALIDÉE</span></p>

    <h2>Identification de la demande</h2>
    <table class="details">
        <tr><th>Numéro de suivi</th><td class="numero">{{ $demande->numero }}</td></tr>
        <tr><th>NPI du demandeur</th><td>{{ $demande->npi }}</td></tr>
        <tr><th>Type d'acte</th><td>{{ $demande->type_acte->label() }}</td></tr>
        <tr><th>Nombre de copies</th><td>{{ $demande->nombre_copies }}</td></tr>
        <tr><th>Statut</th><td>{{ $demande->statut->label() }}</td></tr>
        <tr><th>Date de dépôt</th><td>{{ $demande->created_at->format('d/m/Y à H:i') }}</td></tr>
        <tr><th>Date de validation</th><td>{{ $dateValidation->format('d/m/Y à H:i') }}</td></tr>
    </table>

    <h2>Historique du traitement</h2>
    <table class="historique">
        <thead><tr><th>Date</th><th>Changement de statut</th></tr></thead>
        <tbody>
            @foreach ($demande->historiques as $h)
                <tr>
                    <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $h->ancien_statut?->label() ?? 'Dépôt' }} &rarr; {{ $h->nouveau_statut->label() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pied">
        Ce document récapitule les informations de votre demande validée. Conservez votre numéro de suivi
        <strong>{{ $demande->numero }}</strong> pour toute correspondance.
    </div>
</body>
</html>
