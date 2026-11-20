<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Offre d'un événement : quota d'invités (§32). */
class OffreController extends Controller
{
    public function show(Request $request, Event $event)
    {
        abort_unless($request->user()->canReadOrg($event->organization_id), 403, 'Accès refusé.');

        return view('espace.offres.show', [
            'event' => $event,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->orderBy('max_guests')->get(),
            'guests' => $event->guests()->count(),
            'limit' => $event->guestLimit(),
            'canWrite' => $request->user()->canManageOrg($event->organization_id),
        ]);
    }

    public function choose(Request $request, Event $event, Plan $plan): RedirectResponse
    {
        abort_unless($request->user()->canManageOrg($event->organization_id), 403, 'Droits insuffisants.');
        abort_unless($plan->is_active, 404);

        if ($event->guests()->count() > $plan->max_guests) {
            return back()->withErrors(['offre' => "Cet événement a déjà plus de {$plan->max_guests} invités : choisissez une offre plus grande."]);
        }

        // Seule l'offre d'entrée gratuite s'active seule ; les autres s'activent après paiement.
        $libre = $plan->price == 0 && $plan->max_guests <= Event::DEFAULT_GUEST_LIMIT;
        if (! $libre) {
            return back()->withErrors(['offre' => 'Cette offre s\'active après paiement. Le paiement en ligne arrive bientôt : contactez l\'équipe ATAABA pour l\'activer.']);
        }

        $event->update(['plan_id' => $plan->id]);

        return back()->with('succes', "Offre « {$plan->name} » activée.");
    }
}
