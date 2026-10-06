<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre') | ASIN</title>
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#222}
        .box{background:#fff;border-radius:10px;padding:40px 48px;max-width:460px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.08)}
        .code{font-size:56px;font-weight:700;color:#1f3a5f;margin:0}
        a{display:inline-block;margin-top:18px;padding:10px 20px;background:#1f3a5f;color:#fff;border-radius:6px;text-decoration:none}
    </style>
</head>
<body><div class="box">
    <p class="code">@yield('code')</p>
    <h2>@yield('titre')</h2>
    <p>@yield('message')</p>
    <a href="{{ url('/') }}">Retour à l'accueil</a>
</div></body>
</html>
