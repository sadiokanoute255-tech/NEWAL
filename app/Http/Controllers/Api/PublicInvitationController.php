<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Rsvp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Endpoints publics : l'invité n'a pas de compte, il utilise son lien unique (§13, §17, §48). */
class PublicInvitationController extends Controller
{
    public function show(string $token)
    {
        $inv = $this->find($token);

        // Suivi d'ouverture
        $updates = ['open_count' => $inv->open_count + 1];
        if (! $inv->first_opened_at) {
            $updates['first_opened_at'] = now();
        }
        if (in_array($inv->status, ['creee', 'envoyee', 'distribuee'], true)) {
            $updates['status'] = 'ouverte';
        }
        $inv->update($updates);

        return $this->payload($inv->fresh(['guest', 'event.programItems', 'rsvp']));
    }

    public function rsvp(Request $request, string $token)
    {
        $inv = $this->find($token);
        $event = $inv->event;
        $guest = $inv->guest;

        if ($inv->status === 'arrivee') {
            abort(422, "Vous êtes déjà enregistré(e) à l'entrée.");
        }
        if (! $this->rsvpOpen($event)) {
            abort(422, 'Les réponses ne sont plus ouvertes pour cet événement.');
        }

        $data = $request->validate([
            'response' => 'required|in:accepte,refuse',
            'party_size' => 'nullable|integer|min:1',
            'companion_name' => 'nullable|string|max:255',
            'dietary_preferences' => 'nullable|string|max:255',
            'special_needs' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:1000',
        ]);

        $accepted = $data['response'] === 'accepte';
        $size = $accepted ? ($data['party_size'] ?? 1) : 0;

        if ($accepted && $size > $guest->allowed_seats) {
            throw ValidationException::withMessages([
                'party_size' => ["Cette invitation autorise au maximum {$guest->allowed_seats} participant(s)."],
            ]);
        }

        Rsvp::updateOrCreate(
            ['invitation_id' => $inv->id],
            [
                'response' => $data['response'],
                'party_size' => $size,
                'companion_name' => $data['companion_name'] ?? null,
                'dietary_preferences' => $data['dietary_preferences'] ?? null,
                'special_needs' => $data['special_needs'] ?? null,
                'comment' => $data['comment'] ?? null,
                'responded_at' => now(),
            ]
        );

        $inv->update(['status' => $accepted ? 'confirmee' : 'refusee']);

        return $this->payload($inv->fresh(['guest', 'event.programItems', 'rsvp']));
    }

    // ---------------------------------------------------------------

    private function find(string $token): Invitation
    {
        $inv = Invitation::where('token', $token)
            ->with(['guest', 'event.programItems', 'rsvp'])
            ->first();

        // Réponse volontairement identique pour un token inconnu, un invité supprimé ou un événement archivé
        if (! $inv || ! $inv->guest || ! $inv->event || $inv->event->status === 'archive') {
            abort(404, 'Invitation introuvable.');
        }

        return $inv;
    }

    private function rsvpOpen($event): bool
    {
        if ($event->status !== 'publie') {
            return false;
        }
        $deadline = $event->rsvp_settings['deadline'] ?? null;
        if ($deadline && Carbon::parse($deadline)->endOfDay()->isPast()) {
            return false;
        }

        return true;
    }

    private function payload(Invitation $inv): array
    {
        $e = $inv->event;
        $confirmed = in_array($inv->status, ['confirmee', 'arrivee'], true);

        return [
            'guest' => [
                'display_name' => $inv->guest->display_name,
                'allowed_seats' => $inv->guest->allowed_seats,
            ],
            'invitation' => [
                'code' => $inv->code,
                'status' => $inv->status,
                // Le QR n'est révélé qu'après confirmation (§48)
                'qr_token' => $confirmed ? $inv->qr_token : null,
            ],
            'event' => $e->only([
                'name', 'type', 'description', 'starts_at', 'ends_at', 'timezone',
                'venue_name', 'address', 'latitude', 'longitude', 'map_url', 'cover_path',
                'dress_code', 'instructions',
                'contact_name', 'contact_phone', 'contact_email', 'website', 'social_links',
                'rsvp_settings', 'invitation_design',
            ]) + [
                'program' => $e->programItems
                    ->map(fn ($p) => $p->only(['time', 'title', 'description']))
                    ->values(),
            ],
            'rsvp' => $inv->rsvp,
            'rsvp_open' => $this->rsvpOpen($e),
        ];
    }
}
