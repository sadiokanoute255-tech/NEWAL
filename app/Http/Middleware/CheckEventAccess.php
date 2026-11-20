<?php

namespace App\Http\Middleware;

use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\Invitation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Refuse l'accès à un événement qui n'est pas affecté au collaborateur. */
class CheckEventAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $request->route() || $request->routeIs('admin.*')) {
            return $next($request);
        }

        $event = $this->evenement($request->route()->parameters());

        if ($event && ! $user->canAccessEvent($event)) {
            abort(403, 'Cet événement ne fait pas partie de vos accès.');
        }

        return $next($request);
    }

    /** Retrouve l'événement concerné par la route (directement ou via l'invité, le cercle, l'invitation ou l'entrée). */
    private function evenement(array $p): ?Event
    {
        $trouver = fn (string $classe, mixed $v) => $v instanceof $classe ? $v : $classe::find($v);

        if (isset($p['event'])) {
            return $trouver(Event::class, $p['event']);
        }
        if (isset($p['guest'])) {
            return $trouver(Guest::class, $p['guest'])?->event;
        }
        if (isset($p['group'])) {
            return $trouver(GuestGroup::class, $p['group'])?->event;
        }
        if (isset($p['invitation'])) {
            return $trouver(Invitation::class, $p['invitation'])?->event;
        }
        if (isset($p['checkIn'])) {
            $entree = $trouver(CheckIn::class, $p['checkIn']);

            return $entree ? Event::find($entree->event_id) : null;
        }

        return null;
    }
}