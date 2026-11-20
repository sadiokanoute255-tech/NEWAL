<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Support\EventOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Statistiques par événement (§30). */
class StatsController extends Controller
{
    public function show(Request $request, Event $event)
    {
        abort_unless($request->user()->canSeeStats($event->organization_id), 403, 'Accès refusé.');

        $tz = EventOptions::tz($event);
        $pct = fn (int|float $a, int|float $b) => $b > 0 ? (int) round($a / $b * 100) : 0;

        // Invitations (invités supprimés exclus)
        $invitations = fn () => Invitation::where('event_id', $event->id)->whereHas('guest');
        $total = $invitations()->count();
        $sent = $invitations()->whereNotNull('sent_at')->count();
        $opened = $invitations()->where('open_count', '>', 0)->count();

        // Réponses
        $rsvps = fn () => Rsvp::whereHas('invitation', fn ($q) => $q
            ->where('event_id', $event->id)->whereHas('guest'));
        $confirmed = $rsvps()->where('response', 'accepte')->count();
        $expected = (int) $rsvps()->where('response', 'accepte')->sum('party_size');
        $declined = $rsvps()->where('response', 'refuse')->count();
        $responded = $confirmed + $declined;
        $awaiting = max(0, $total - $responded);

        // Présences
        $checkIns = fn () => CheckIn::where('event_id', $event->id)->whereNull('cancelled_at');
        $present = (int) $checkIns()->sum('persons_entered');

        $byHour = $checkIns()->get(['checked_in_at', 'persons_entered'])
            ->groupBy(fn ($c) => $c->checked_in_at->copy()->timezone($tz)->format('H:00'))
            ->map(fn ($rows) => (int) $rows->sum('persons_entered'))
            ->sortKeys();

        $recent = CheckIn::where('event_id', $event->id)
            ->whereNull('cancelled_at')
            ->with('invitation.guest')
            ->latest('checked_in_at')
            ->limit(8)
            ->get();

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
                sum(case when rsvps.response = 'accepte' then rsvps.party_size else 0 end) as persons")
            ->get()
            ->keyBy('group_id');

        $groups = $event->groups()->get(['id', 'name', 'color'])->map(function ($g) use ($rows) {
            $r = $rows->get($g->id);

            return [
                'name' => $g->name,
                'color' => $g->color ?: '#B8432C',
                'guests' => (int) ($r->guests ?? 0),
                'confirmed' => (int) ($r->confirmed ?? 0),
                'declined' => (int) ($r->declined ?? 0),
                'persons' => (int) ($r->persons ?? 0),
            ];
        })->values()->all();

        if ($rows->has(null)) {
            $r = $rows->get(null);
            $groups[] = [
                'name' => 'Sans cercle', 'color' => '#5c5247',
                'guests' => (int) $r->guests, 'confirmed' => (int) $r->confirmed,
                'declined' => (int) $r->declined, 'persons' => (int) $r->persons,
            ];
        }

        return view('espace.statistiques.show', [
            'event' => $event,
            'tz' => $tz,
            'guests' => $event->guests()->count(),
            'total' => $total,
            'sent' => $sent,
            'opened' => $opened,
            'openRate' => min(100, $pct($opened, $sent)),
            'confirmed' => $confirmed,
            'declined' => $declined,
            'awaiting' => $awaiting,
            'responseRate' => $pct($responded, $total),
            'expected' => $expected,
            'present' => $present,
            'attendanceRate' => $pct($present, $expected),
            'pConfirmed' => $pct($confirmed, $total),
            'pDeclined' => $pct($declined, $total),
            'pAwaiting' => $pct($awaiting, $total),
            'byHour' => $byHour,
            'recent' => $recent,
            'groups' => $groups,
        ]);
    }
}