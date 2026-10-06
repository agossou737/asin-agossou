<?php

return [
    // Destinataire des alertes « nouvelle demande a traiter ».
    'admin_email' => env('ADMIN_EMAIL', 'admin@asin.bj'),

    // Mot de passe du compte admin cree par AdminSeeder (aleatoire et affiche si vide).
    'admin_password' => env('ADMIN_PASSWORD'),

    // Jeton Bearer pour l'API de traitement (PATCH /api/demandes/{id}/statut). Vide = API desactivee.
    'api_token' => env('ADMIN_API_TOKEN'),
];
