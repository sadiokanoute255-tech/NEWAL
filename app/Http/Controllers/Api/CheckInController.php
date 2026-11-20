<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesEvent;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Contrôle d'accès : scan QR, recherche manuelle, validation, annulation (§21-23). */
class CheckInController extends Controller
{
    use AuthorizesEvent;

    /** Étape 1 : le scan affiche la fiche de l'invité, sans rien valider. */
    public function lookup(Request $request, Event $event)
    {
        $this->ensureCanScan($request, $event);

        $inv = $this->findInvitation($event, $this->identifier($request));
        if (! $inv) {
            return [
                'result' => 'invalid',
                'message' => 'QR Code ou code invalide pour cet événement.',
            ];
        }

        return $this->describe($event, $inv);
    }

    /** Recherche manuelle par nom, téléphone ou code (§23). */
    public function search(Request $request, Event $event)
    {
        $this->ensureCanScan($request, $event);

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return ['data' => []];
        }

        $invitations = Invitation::where('event_id', $event->id)
            ->whereHas('guest')
            ->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")
                  ->orWhereHas('guest', function ($g) use ($q) {
                      $g->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('whatsapp', 'like', "%{$q}%");
                  });
            })
            ->with(['guest.group', 'rsvp'])
            ->limit(20)
            ->get();

        return ['data' => $invitations->map(fn ($i) => $this->describe($event, $i))->values()];
    }

    /** Étape 2 : validation de l'entrée (totale ou partielle d'un groupe). */
    public function store(Request $request, Event $event)
    {
        $this->ensureCanScan($request, $event);

        if ($event->status !== 'publie') {
            abort(422, "L'événement n'est pas ouvert au contrôle d'accès.");
        }

        $data = $this->identifier($request, [
            'persons' => 'required|integer|min:1|max:100',
            'force' => 'sometimes|boolean',
        ]);

        $force = (bool) ($data['force'] ?? false);
        if ($force) {
            abort_unless($request->user()->canManageOrg($event->organization_id), 403, 'Seul un gestionnaire peut forcer une entrée.');
        }

        $found = $this->findInvitation($event, $data);
        abort_if(! $found, 422, 'QR Code ou code invalide pour cet événement.');

        $persons = (int) $data['persons'];

        // Verrou sur l'invitation : deux scans simultanés ne peuvent pas dépasser les places (§22)
        $checkIn = DB::transaction(function () use ($found, $event, $request, $persons, $force) {
            $inv = Invitation::whereKey($found->id)->lockForUpdate()->first();
            $inv->load(['guest.group', 'rsvp']);

            if ($inv->status === 'refusee' && ! $force) {
                abort(422, "Cet invité a décliné l'invitation.");
            }

            $seats = $this->seats($inv);
            if ($seats['remaining'] <= 0) {
                abort(422, "Invitation déjà utilisée : les {$seats['capacity']} place(s) sont déjà entrées.");
            }
            if ($persons > $seats['remaining']) {
                abort(422, "Il ne reste que {$seats['remaining']} place(s) pour cette invitation.");
            }

            $checkIn = CheckIn::create([
                'invitation_id' => $inv->id,
                'event_id' => $event->id,
                'agent_id' => $request->user()->id,
                'persons_entered' => $persons,
                'checked_in_at' => now(),
            ]);
            $inv->update(['status' => 'arrivee']);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'organization_id' => $event->organization_id,
                'action' => 'scanned',
                'subject_type' => Invitation::class,
                'subject_id' => $inv->id,
                'meta' => ['persons' => $persons, 'forced' => $force],
                'ip' => $request->ip(),
            ]);

            return $checkIn;
        });

        $payload = $this->describe($event, $found->fresh(['guest.group', 'rsvp']));
        $payload['result'] = 'checked_in';
        $payload['message'] = "Entrée validée : {$persons} personne(s).";
        $payload['check_in_id'] = $checkIn->id;

        return response()->json($payload, 201);
    }

    /** Historique des entrées (y compris annulées). */
    public function index(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);

        return CheckIn::where('event_id', $event->id)
            ->with(['invitation.guest:id,first_name,last_name', 'agent:id,name,first_name'])
            ->latest('checked_in_at')
            ->paginate(50);
    }

    /** Annulation d'une entrée validée par erreur (gestionnaire/propriétaire). */
    public function cancel(Request $request, CheckIn $checkIn)
    {
        $event = Event::findOrFail($checkIn->event_id);
        $this->ensureCanWrite($request, $event);

        $data = $request->validate(['reason' => 'nullable|string|max:255']);

        if ($checkIn->cancelled_at) {
            abort(422, 'Cette entrée est déjà annulée.');
        }

        DB::transaction(function () use ($checkIn, $event, $request, $data) {
            $inv = Invitation::whereKey($checkIn->invitation_id)->lockForUpdate()->first();

            $checkIn->update([
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
                'cancel_reason' => $data['reason'] ?? null,
            ]);

            $entered = (int) CheckIn::where('invitation_id', $inv->id)
                ->whereNull('cancelled_at')->sum('persons_entered');

            if ($entered === 0) {
                $rsvp = $inv->rsvp;
                $inv->update(['status' => $rsvp
                    ? ($rsvp->response === 'accepte' ? 'confirmee' : 'refusee')
                    : 'ouverte']);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'organization_id' => $event->organization_id,
                'action' => 'checkin_cancelled',
                'subject_type' => Invitation::class,
                'subject_id' => $inv->id,
                'meta' => ['check_in_id' => $checkIn->id, 'reason' => $data['reason'] ?? null],
                'ip' => $request->ip(),
            ]);
        });

        return response()->json(['message' => 'Entrée annulée.']);
    }

    // ---------------------------------------------------------------

    /** Propriétaire, gestionnaire et agent d'accueil peuvent contrôler les entrées. */
    private function ensureCanScan(Request $request, Event $event): void
    {
        $role = $request->user()->roleIn($event->organization_id);
        abort_unless(in_array($role, ['proprietaire', 'gestionnaire', 'accueil'], true), 403, 'Droits insuffisants.');
    }

    private function identifier(Request $request, array $extraRules = []): array
    {
        $data = $request->validate(array_merge([
            'qr_token' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:20',
            'invitation_id' => 'nullable|integer',
        ], $extraRules));

        if (empty($data['qr_token']) && empty($data['code']) && empty($data['invitation_id'])) {
            throw ValidationException::withMessages([
                'qr_token' => ['Fournissez un QR token, un code ou un identifiant d\'invitation.'],
            ]);
        }

        return $data;
    }

    /** Retourne l'invitation seulement si elle appartient à cet événement. */
    private function findInvitation(Event $event, array $data): ?Invitation
    {
        $q = Invitation::where('event_id', $event->id)->with(['guest.group', 'rsvp']);

        if (! empty($data['qr_token'])) {
            $q->where('qr_token', trim($data['qr_token']));
        } elseif (! empty($data['code'])) {
            $q->where('code', strtoupper(trim($data['code'])));
        } else {
            $q->whereKey($data['invitation_id']);
        }

        $inv = $q->first();

        return ($inv && $inv->guest) ? $inv : null;
    }

    /** Places : capacité (RSVP confirmé, sinon places autorisées), déjà entrées, restantes. */
    private function seats(Invitation $inv): array
    {
        $capacity = ($inv->rsvp && $inv->rsvp->response === 'accepte')
            ? (int) $inv->rsvp->party_size
            : (int) $inv->guest->allowed_seats;

        $entered = (int) CheckIn::where('invitation_id', $inv->id)
            ->whereNull('cancelled_at')->sum('persons_entered');

        return [
            'capacity' => $capacity,
            'entered' => $entered,
            'remaining' => max(0, $capacity - $entered),
        ];
    }

    private function describe(Event $event, Invitation $inv): array
    {
        $seats = $this->seats($inv);
        $tz = $event->timezone ?: 'Africa/Dakar';

        $last = CheckIn::where('invitation_id', $inv->id)
            ->whereNull('cancelled_at')
            ->with('agent:id,name,first_name')
            ->latest('checked_in_at')
            ->first();

        if ($inv->status === 'refusee') {
            $result = 'declined';
            $message = "Cet invité a décliné l'invitation.";
        } elseif ($seats['remaining'] <= 0) {
            $result = 'already_used';
            $message = 'Invitation déjà utilisée'
                .($last ? ' à '.$last->checked_in_at->copy()->timezone($tz)->format('H\hi') : '').'.';
        } else {
            $result = 'ok';
            $message = 'Invitation valide.';
        }

        return [
            'result' => $result,
            'message' => $message,
            'invitation' => [
                'id' => $inv->id,
                'code' => $inv->code,
                'status' => $inv->status,
            ],
            'guest' => [
                'display_name' => $inv->guest->display_name,
                'group' => $inv->guest->group?->name,
                'organization' => $inv->guest->organization,
                'job_title' => $inv->guest->job_title,
            ],
            'rsvp' => $inv->rsvp?->only(['response', 'party_size', 'companion_name', 'special_needs']),
            'seats' => $seats,
            'last_check_in' => $last ? [
                'at' => $last->checked_in_at->toIso8601String(),
                'agent' => trim(($last->agent?->first_name ?? '').' '.($last->agent?->name ?? '')),
            ] : null,
        ];
    }
}
