<?php

return [
    // URL du frontend React : sert à construire le lien public des invitations (/i/{token})
    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'),

    // Indicatif ajouté aux numéros locaux pour les liens WhatsApp (221 = Sénégal)
    'default_country_code' => env('DEFAULT_COUNTRY_CODE', '221'),
];
