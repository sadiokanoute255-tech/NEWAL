<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\Rsvp;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
            if ($request->user()->is_admin) {
            return redirect()->route('admin.tableau');
        } 
                if ($request->user()->mainRole() === 'accueil') {
            return redirect()->route('section.controle');
        }
            $base = Event::accessibleBy($request->user())->where('status', '!=', 'archive');
        $eventIds = (clone $base)->pluck('id');

        $invitations = fn () => Invitation::whereIn('event_id', $eventIds)->whereHas('guest');
        $rsvps = fn () => Rsvp::whereHas('invitation', fn ($q) => $q
            ->whereIn('event_id', $eventIds)->whereHas('guest'));

        $stats = [
            'events' => (clone $base)->count(),
            'upcoming' => (clone $base)->where('starts_at', '>=', now())->count(),
            'guests' => Guest::whereIn('event_id', $eventIds)->count(),
            'confirmed' => $rsvps()->where('response', 'accepte')->count(),
            'confirmed_persons' => (int) $rsvps()->where('response', 'accepte')->sum('party_size'),
            'present' => (int) CheckIn::whereIn('event_id', $eventIds)->whereNull('cancelled_at')->sum('persons_entered'),
        ];

        $upcoming = (clone $base)
            ->where('starts_at', '>=', now())
            ->withCount('guests')
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        return view('espace.tableau-de-bord', [
            'stats' => $stats,
            'upcoming' => $upcoming,
            'activity' => $this->activity($eventIds),
        ]);
    }

    /** Dernières confirmations, refus et entrées. */
    private function activity($eventIds)
    {
        $reponses = Rsvp::whereHas('invitation', fn ($q) => $q->whereIn('event_id', $eventIds))
            ->with('invitation.guest')
            ->latest('responded_at')
            ->limit(5)
            ->get()
            ->filter(fn ($r) => $r->invitation?->guest)
            ->map(fn ($r) => [
                'icone' => $r->response === 'accepte' ? 'check' : 'x',
                'titre' => $r->response === 'accepte' ? 'Invitation confirmée' : 'Invitation déclinée',
                'detail' => $r->invitation->guest->display_name
                    .($r->response === 'accepte' ? ' · '.$r->party_size.' pers.' : ''),
                'date' => $r->responded_at,
            ]);

        $entrees = CheckIn::whereIn('event_id', $eventIds)
            ->whereNull('cancelled_at')
            ->with('invitation.guest')
            ->latest('checked_in_at')
            ->limit(5)
            ->get()
            ->filter(fn ($c) => $c->invitation?->guest)
            ->map(fn ($c) => [
                'icone' => 'user-check',
                'titre' => 'Entrée enregistrée',
                'detail' => $c->invitation->guest->display_name.' · '.$c->persons_entered.' pers.',
                'date' => $c->checked_in_at,
            ]);

        return $reponses->concat($entrees)->sortByDesc('date')->take(5)->values();
    }
}