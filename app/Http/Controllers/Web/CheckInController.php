<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Support\EventOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Contrôle d'accès (§21 à §23) : scan du QR Code, double utilisation, recherche manuelle. */
class CheckInController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->ensureCanScan($request, $event);

        $active = fn () => CheckIn::where('event_id', $event->id)->whereNull('cancelled_at');

        $expected = (int) Rsvp::whereHas('invitation', fn ($q) => $q
                ->where('event_id', $event->id)->whereHas('guest'))
            ->where('response', 'accepte')
            ->sum('party_size');

        return view('espace.controle.index', [
            'event' => $event,
            'tz' => EventOptions::tz($event),
            'present' => (int) $active()->sum('persons_entered'),
            'arrived' => $active()->distinct()->count('invitation_id'),
            'expected' => $expected,
            'recent' => CheckIn::where('event_id', $event->id)
                ->with('invitation.guest')
                ->latest('checked_in_at')
                ->limit(10)
                ->get(),
            'canCancel' => $request->user()->canManageOrg($event->organization_id),
        ]);
    }

    /** Un QR Code scanné ou un code tapé donne une invitation ; un nom ou un téléphone donne une liste. */
    public function lookup(Request $request, Event $event): JsonResponse
    {
        $this->ensureCanScan($request, $event);

        $q = trim($request->validate(['q' => 'required|string|max:200'])['q']);

        $base = Invitation::where('event_id', $event->id)
            ->whereHas('guest')
            ->with(['guest.group', 'rsvp']);

        $exact = (clone $base)
            ->where(fn ($w) => $w->where('qr_token', $q)->orWhere('code', strtoupper($q)))
            ->first();

        $matches = $exact
            ? collect([$exact])
            : (clone $base)->whereHas('guest', function ($g) use ($q) {
                $g->where(function ($w) use ($q) {
                    $w->where('first_name', 'like', "%{$q}%")
                      ->orWhere('last_name', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%")
                      ->orWhere('whatsapp', 'like', "%{$q}%");
                });
            })->limit(8)->get();

        return response()->json([
            'matches' => $matches->map(fn ($inv) => $this->summary($inv, $event))->values(),
        ]);
    }

    /** Valide l'entrée de N personnes, sans dépasser les places autorisées (§10, §22). */
    public function store(Request $request, Event $event): JsonResponse
    {
        $this->ensureCanScan($request, $event);

        $data = $request->validate([
            'invitation_id' => 'required|integer',
            'persons' => 'required|integer|min:1|max:100',
        ]);

        $invitation = Invitation::where('event_id', $event->id)
            ->whereHas('guest')
            ->with(['guest', 'rsvp'])
            ->findOrFail($data['invitation_id']);

        $erreur = null;

        DB::transaction(function () use ($invitation, $data, $request, $event, &$erreur) {
            $resume = $this->summary($invitation, $event);

            if ($data['persons'] > $resume['reste']) {
                $erreur = $resume['reste'] === 0
                    ? 'Toutes les places de cette invitation sont déjà utilisées.'
                    : "Cette invitation ne permet plus que {$resume['reste']} entrée(s).";

                return;
            }

            CheckIn::create([
                'invitation_id' => $invitation->id,
                'event_id' => $event->id,
                'agent_id' => $request->user()->id,
                'persons_entered' => $data['persons'],
                'checked_in_at' => now(),
            ]);

            $invitation->update(['status' => 'arrivee']);
        });

        if ($erreur) {
            return response()->json(['message' => $erreur], 422);
        }

        return response()->json([
            'ok' => true,
            'nom' => $invitation->guest->display_name,
            'persons' => (int) $data['persons'],
        ]);
    }

    public function cancel(Request $request, CheckIn $checkIn): RedirectResponse
    {
        $event = Event::findOrFail($checkIn->event_id);
        abort_unless($request->user()->canManageOrg($event->organization_id), 403, 'Droits insuffisants.');
        abort_if($checkIn->cancelled_at, 422, 'Entrée déjà annulée.');

        $data = $request->validate(['cancel_reason' => 'nullable|string|max:255']);

        $checkIn->update([
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->id,
            'cancel_reason' => $data['cancel_reason'] ?: null,
        ]);

        // S'il ne reste aucune entrée valide, l'invitation retrouve son statut précédent (§18)
        $invitation = $checkIn->invitation()->with('rsvp')->first();
        $reste = CheckIn::where('invitation_id', $invitation->id)->whereNull('cancelled_at')->exists();
        if (! $reste) {
            $invitation->update(['status' => match ($invitation->rsvp?->response) {
                'accepte' => 'confirmee',
                'refuse' => 'refusee',
                default => 'ouverte',
            }]);
        }

        return back()->with('succes', 'Entrée annulée.');
    }

    // ------------------------------------------------------------------

    private function summary(Invitation $inv, Event $event): array
    {
        $guest = $inv->guest;
        $rsvp = $inv->rsvp;

        $active = CheckIn::where('invitation_id', $inv->id)
            ->whereNull('cancelled_at')
            ->orderBy('checked_in_at')
            ->get();

        $entres = (int) $active->sum('persons_entered');
        $places = max(1, (int) $guest->allowed_seats);
        $reste = max(0, $places - $entres);
        $attendu = ($rsvp && $rsvp->response === 'accepte') ? min((int) $rsvp->party_size, $places) : $places;

        $etat = 'ok';
        if ($entres > 0) {
            $etat = $reste > 0 ? 'partiel' : 'complet';
        } elseif ($rsvp && $rsvp->response === 'refuse') {
            $etat = 'decline';
        }

        $premier = $active->first()?->checked_in_at
            ?->copy()->timezone(EventOptions::tz($event))->format('H \h i');

        return [
            'id' => $inv->id,
            'nom' => $guest->display_name,
            'cercle' => $guest->group?->name,
            'code' => $inv->code,
            'places' => $places,
            'reponse' => $rsvp?->response ?? 'aucune',
            'attendu' => $attendu,
            'entres' => $entres,
            'reste' => $reste,
            'defaut' => $reste > 0 ? max(1, min($reste, $attendu - $entres > 0 ? $attendu - $entres : 1)) : 0,
            'etat' => $etat,
            'premier' => $premier,
        ];
    }

    private function ensureCanScan(Request $request, Event $event): void
    {
        abort_unless($request->user()->canScan($event->organization_id), 403, 'Accès refusé.');
    }
}