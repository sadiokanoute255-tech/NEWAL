<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesEvent;
use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\Rsvp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Tableau de bord d'un événement (§25, §26, §34). */
class StatsController extends Controller
{
    use AuthorizesEvent;

    public function show(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);

        $tz = $event->timezone ?: 'Africa/Dakar';

        // Invitations (invités supprimés exclus)
        $invitations = fn () => Invitation::where('event_id', $event->id)->whereHas('guest');

        $byStatus = array_fill_keys(
            ['creee', 'envoyee', 'distribuee', 'ouverte', 'confirmee', 'refusee', 'sans_reponse', 'arrivee'],
            0
        );
        foreach ($invitations()->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status') as $status => $count) {
            $byStatus[$status] = (int) $count;
        }
        $totalInvitations = array_sum($byStatus);

        // Réponses RSVP
        $rsvps = fn () => Rsvp::whereHas('invitation', fn ($q) => $q
            ->where('event_id', $event->id)->whereHas('guest'));

        $confirmed = $rsvps()->where('response', 'accepte')->count();
        $confirmedPersons = (int) $rsvps()->where('response', 'accepte')->sum('party_size');
        $declined = $rsvps()->where('response', 'refuse')->count();
        $responded = $confirmed + $declined;

        // Présences
        $checkIns = fn () => CheckIn::where('event_id', $event->id)->whereNull('cancelled_at');

        $present = (int) $checkIns()->sum('persons_entered');

        $byHour = $checkIns()->get(['checked_in_at', 'persons_entered'])
            ->groupBy(fn ($c) => $c->checked_in_at->copy()->timezone($tz)->format('H:00'))
            ->map(fn ($rows) => (int) $rows->sum('persons_entered'))
            ->sortKeys();

        $recent = CheckIn::where('event_id', $event->id)
            ->whereNull('cancelled_at')
            ->with('invitation.guest:id,first_name,last_name')
            ->latest('checked_in_at')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'guest' => $c->invitation?->guest?->display_name,
                'persons' => $c->persons_entered,
                'at' => $c->checked_in_at->toIso8601String(),
            ]);

        // Par cercle
        $rows = DB::table('guests')
            ->leftJoin('invitations', 'invitations.guest_id', '=', 'guests.id')
            ->leftJoin('rsvps', 'rsvps.invitation_id', '=', 'invitations.id')
            ->where('guests.event_id', $event->id)
            ->whereNull('guests.deleted_at')
            ->groupBy('guests.guest_group_id')
            ->selectRaw("guests.guest_group_id as group_id,
                count(distinct guests.id) as guests,
                sum(case when rsvps.response = 'accepte' then 1 else 0 end) as confirmed,
                sum(case when rsvps.response = 'refuse' then 1 else 0 end) as declined,
                sum(case when rsvps.response = 'accepte' then rsvps.party_size else 0 end) as confirmed_persons")
            ->get()
            ->keyBy('group_id');

        $groups = $event->groups()->get(['id', 'name', 'color'])
            ->map(function ($g) use ($rows) {
                $r = $rows->get($g->id);

                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'color' => $g->color,
                    'guests' => (int) ($r->guests ?? 0),
                    'confirmed' => (int) ($r->confirmed ?? 0),
                    'declined' => (int) ($r->declined ?? 0),
                    'confirmed_persons' => (int) ($r->confirmed_persons ?? 0),
                ];
            })->values()->all();

        if ($rows->has(null)) {
            $r = $rows->get(null);
            $groups[] = [
                'id' => null, 'name' => 'Sans cercle', 'color' => null,
                'guests' => (int) $r->guests, 'confirmed' => (int) $r->confirmed,
                'declined' => (int) $r->declined, 'confirmed_persons' => (int) $r->confirmed_persons,
            ];
        }

        return [
            'event' => $event->only(['id', 'name', 'status', 'starts_at']),
            'guests' => [
                'total' => $event->guests()->count(),
                'seats' => (int) $event->guests()->sum('allowed_seats'),
                'limit' => $event->guestLimit(),
            ],
            'invitations' => [
                'total' => $totalInvitations,
                'by_status' => $byStatus,
                'sent' => $invitations()->whereNotNull('sent_at')->count(),
                'opened' => $invitations()->where('open_count', '>', 0)->count(),
            ],
            'rsvp' => [
                'confirmed' => $confirmed,
                'confirmed_persons' => $confirmedPersons,
                'declined' => $declined,
                'awaiting' => max(0, $totalInvitations - $responded),
                'response_rate' => $totalInvitations > 0 ? round($responded / $totalInvitations * 100, 1) : 0,
            ],
            'attendance' => [
                'persons_present' => $present,
                'invitations_arrived' => $checkIns()->distinct()->count('invitation_id'),
                'expected_persons' => $confirmedPersons,
                'rate' => $confirmedPersons > 0 ? round($present / $confirmedPersons * 100, 1) : 0,
                'by_hour' => $byHour,
                'recent' => $recent,
            ],
            'groups' => $groups,
        ];
    }
}
