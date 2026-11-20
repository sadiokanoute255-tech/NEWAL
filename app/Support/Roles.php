<?php

namespace App\Support;

/** Rôles et droits des membres d'une organisation (§40). */
class Roles
{
    public const LIBELLES = [
        'proprietaire' => 'Propriétaire',
        'gestionnaire' => 'Gestionnaire événement',
        'communication' => 'Communication',
        'accueil' => 'Accueil',
        'lecture_seule' => 'Lecture seule',
    ];

    public const DESCRIPTIONS = [
        'gestionnaire' => 'Gère les événements, les invités et les invitations, scanne les entrées et voit les statistiques.',
        'communication' => 'Prépare et envoie les invitations. Voit les invités en lecture seule.',
        'accueil' => 'Scanne les QR Codes et enregistre les entrées. Rien d\'autre.',
        'lecture_seule' => 'Consulte les événements, invités, invitations et statistiques sans rien modifier.',
    ];

    /** Rôles que le propriétaire peut attribuer. */
    public const ATTRIBUABLES = ['gestionnaire', 'communication', 'accueil', 'lecture_seule'];

    /** Consulter les événements, invités et invitations. */
    public const LECTURE = ['proprietaire', 'gestionnaire', 'communication', 'lecture_seule'];

    /** Consulter les statistiques. */
    public const STATS = ['proprietaire', 'gestionnaire', 'lecture_seule'];

    /** Générer et envoyer les invitations. */
    public const INVITATIONS = ['proprietaire', 'gestionnaire', 'communication'];

    /** Scanner les QR Codes et valider les entrées. */
    public const SCAN = ['proprietaire', 'gestionnaire', 'accueil'];
}