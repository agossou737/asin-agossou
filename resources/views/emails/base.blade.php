<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>@yield('objet')</title></head>
<body style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#222;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#fff;border-radius:8px;">
        <tr><td style="padding:20px 28px;background:#1f3a5f;color:#fff;border-radius:8px 8px 0 0;font-size:18px;font-weight:bold;">ASIN &middot; Demandes d'actes</td></tr>
        <tr><td style="padding:28px;line-height:1.55;font-size:15px;">
            @yield('contenu')
            <table role="presentation" cellpadding="6" cellspacing="0" style="margin-top:16px;width:100%;background:#f4f5f7;border-radius:6px;font-size:14px;">
                <tr><td><strong>Numéro de suivi</strong></td><td style="font-weight:bold;letter-spacing:1px;">{{ $demande->numero }}</td></tr>
                <tr><td><strong>NPI</strong></td><td>{{ $demande->npi }}</td></tr>
                <tr><td><strong>Type d'acte</strong></td><td>{{ $demande->type_acte->label() }}</td></tr>
                <tr><td><strong>Copies</strong></td><td>{{ $demande->nombre_copies }}</td></tr>
                <tr><td><strong>Statut</strong></td><td>{{ $demande->statut->label() }}</td></tr>
            </table>
        </td></tr>
        <tr><td style="padding:16px 28px;font-size:12px;color:#888;">Message automatique, merci de ne pas y répondre.</td></tr>
    </table>
</body>
</html>
