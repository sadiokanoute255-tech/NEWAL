<?php

namespace App\Support;

use App\Models\Event;
use Carbon\Carbon;

class EventOptions
{
    public const TYPES = [
        'mariage' => 'Mariage',
        'bapteme' => 'Baptême',
        'anniversaire' => 'Anniversaire',
        'conference' => 'Conférence',
        'seminaire' => 'Séminaire',
        'atelier' => 'Atelier',
        'lancement' => 'Lancement de produit',
        'ceremonie' => 'Cérémonie officielle',
        'assemblee_generale' => 'Assemblée générale',
        'cocktail' => 'Cocktail',
        'gala' => 'Gala',
        'diner' => 'Dîner ou réception',
        'entreprise' => "Événement d'entreprise",
        'association' => 'Événement associatif',
        'autre' => 'Autre',
    ];

    public const STATUS = [
        'brouillon' => 'Brouillon',
        'publie' => 'Publié',
        'archive' => 'Archivé',
    ];

    public const VISIBILITY = [
        'prive' => 'Privé',
        'sur_invitation' => 'Sur invitation uniquement',
        'public_inscription' => 'Public avec inscription',
    ];

    public const TIMEZONES = [
        'Africa/Dakar' => 'Dakar (GMT)',
        'Africa/Abidjan' => 'Abidjan (GMT)',
        'Africa/Bamako' => 'Bamako (GMT)',
        'Africa/Casablanca' => 'Casablanca',
        'Europe/Paris' => 'Paris',
        'UTC' => 'UTC',
    ];

    public const RSVP = [
        'ask_party_size' => 'Nombre de participants',
        'ask_companion' => "Nom de l'accompagnant",
        'ask_diet' => 'Préférences alimentaires',
        'ask_special_needs' => 'Besoins particuliers',
        'ask_comment' => 'Commentaire',
    ];

    public static function typeLabel(?string $type): string
    {
        return self::TYPES[$type] ?? ucfirst((string) $type);
    }

    public static function tz(Event $event): string
    {
        return $event->timezone ?: 'Africa/Dakar';
    }

    /** Date de l'événement, affichée dans son fuseau, en français. */
    public static function date(Event $event, string $format = 'l j F Y, H:i'): string
    {
        return $event->starts_at
            ->copy()
            ->timezone(self::tz($event))
            ->locale('fr')
            ->translatedFormat($format);
    }

    /** Convertit une date saisie (dans le fuseau de l'événement) vers le fuseau de l'application. */
    public static function toApp(string $local, string $tz): Carbon
    {
        return Carbon::parse($local, $tz)->setTimezone(config('app.timezone'));
    }
}