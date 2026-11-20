<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Rsvp;
use App\Models\User;
use Carbon\Carbon;

/** Compteur des nouvelles notifications : réponses arrivées depuis la dernière visite. */
class Notifs
{
    /** Date de la dernière consultation des notifications. */
    public static function vuLe(User $user): Carbon
    {
        return $user->notifications_seen_at
            ? Carbon::parse($user->notifications_seen_at)
            : ($user->created_at ?? now());
    }
    public static function nouvelles(User $user): int
    {
        $eventIds = Event::accessibleBy($user)
            ->where('status', '!=', 'archive')
            ->select('events.id');

        return Rsvp::whereHas('invitation', fn ($q) => $q
                ->whereIn('event_id', $eventIds)
                ->whereHas('guest'))
            ->where('responded_at', '>', self::vuLe($user))
            ->count();
    }
}