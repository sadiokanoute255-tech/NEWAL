<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Rsvp;
use App\Support\Notifs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Notifications de l'organisateur (§29.1), calculées à partir de l'activité. */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $vuLe = Notifs::vuLe($user); // pour repérer les lignes « Nouveau »
        $events = Event::accessibleBy($user)
            ->where('status', '!=', 'archive')
            ->withCount('guests')
            ->with('plan')
            ->get();

        // Alertes : événement proche, quota presque atteint
        $alertes = collect();
        foreach ($events as $e) {
            if ($e->starts_at->isFuture() && $e->starts_at->lte(now()->addDays(7))) {
                $jours = (int) round(now()->startOfDay()->diffInDays($e->starts_at->copy()->startOfDay()));
                $quand = $jours <= 0 ? 'aujourd\'hui' : ($jours === 1 ? 'demain' : "dans {$jours} jours");
                $alertes->push([
                    'icone' => 'calendar',
                    'titre' => 'Événement proche',
                    'detail' => "{$e->name} a lieu {$quand}.",
                    'lien' => route('evenements.show', $e),
                ]);
            }

            $limite = $e->guestLimit();
            if ($limite > 0 && $e->guests_count >= $limite * 0.8) {
                $alertes->push([
                    'icone' => 'users',
                    'titre' => 'Quota presque atteint',
                    'detail' => "{$e->name} : {$e->guests_count} / {$limite} invités.",
                    'lien' => route('offres.show', $e),
                ]);
            }
        }

        // Activité : dernières confirmations et refus
        $activite = Rsvp::whereHas('invitation', fn ($q) => $q->whereIn('event_id', $events->pluck('id')))
            ->with('invitation.guest', 'invitation.event')
            ->latest('responded_at')
            ->limit(20)
            ->get()
            ->filter(fn ($r) => $r->invitation?->guest && $r->invitation?->event)
            ->map(function ($r) {
                $accepte = $r->response === 'accepte';

                return [
                    'icone' => $accepte ? 'check' : 'x',
                    'titre' => $accepte ? 'Nouvelle confirmation' : 'Nouveau refus',
                    'detail' => $r->invitation->guest->display_name.' · '.$r->invitation->event->name
                        .($accepte ? ' · '.$r->party_size.' pers.' : ''),
                    'date' => $r->responded_at,
                    'lien' => route('invites.index', $r->invitation->event),
                ];
            })->values();

        // La page est consultée : tout est désormais « vu »
        $user->forceFill(['notifications_seen_at' => now()])->save();

        return view('espace.notifications', compact('alertes', 'activite', 'vuLe'));
    }

    /** Nombre de nouvelles réponses, lu régulièrement par la cloche. */
    public function compteur(Request $request): JsonResponse
    {
        return response()->json(['nouvelles' => Notifs::nouvelles($request->user())]);
    }
}