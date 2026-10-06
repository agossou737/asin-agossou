<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Rappel : demandes à traiter</title></head>
<body style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#222;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;margin:0 auto;background:#fff;border-radius:8px;">
        <tr><td style="padding:20px 28px;background:#1f3a5f;color:#fff;border-radius:8px 8px 0 0;font-size:18px;font-weight:bold;">ASIN &middot; Rappel des demandes à traiter</td></tr>
        <tr><td style="padding:28px;line-height:1.55;font-size:15px;">
            <p>Bonjour,</p>
            <p>
                <strong>{{ $compteurs['deposee'] ?? 0 }}</strong> demande(s) <strong>en attente</strong>
                et <strong>{{ $compteurs['en_cours'] ?? 0 }}</strong> demande(s) <strong>en cours</strong> de traitement
                nécessitent votre attention.
            </p>

            <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                <tr style="background:#1f3a5f;color:#fff;text-align:left;">
                    <th>Numéro</th><th>Type d'acte</th><th>Statut</th><th>Déposée</th>
                </tr>
                @foreach ($demandes as $d)
                    <tr style="border-bottom:1px solid #e3e6ea;">
                        <td><a href="{{ route('admin.demandes.show', $d->id) }}" style="color:#1f3a5f;font-weight:bold;">{{ $d->numero }}</a></td>
                        <td>{{ $d->type_acte->label() }}</td>
                        <td>{{ $d->statut->label() }}</td>
                        <td>{{ $d->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </table>

            @if ($reste > 0)
                <p style="color:#666;">… et {{ $reste }} autre(s) demande(s) non listée(s).</p>
            @endif

            <p style="margin-top:20px;">
                <a href="{{ route('admin.demandes', ['statut' => 'deposee']) }}" style="display:inline-block;padding:10px 18px;background:#1f3a5f;color:#fff;border-radius:6px;text-decoration:none;">Traiter les demandes</a>
            </p>
        </td></tr>
        <tr><td style="padding:16px 28px;font-size:12px;color:#888;">Rappel automatique envoyé chaque jour à 8h00 et 16h00. Merci de ne pas y répondre.</td></tr>
    </table>
</body>
</html>
