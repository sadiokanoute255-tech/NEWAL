<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Support\EventOptions;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Page publique de l'invitation (§48) : aucun compte requis, le lien suffit. */
class PublicInvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = $this->find($token);
        $event = $invitation->event;

        // §18 : première ouverture → statut « Ouverte »
        if (! session()->has('merci')) {
            $invitation->open_count = $invitation->open_count + 1;
            $invitation->first_opened_at = $invitation->first_opened_at ?? now();
            if (in_array($invitation->status, ['creee', 'envoyee', 'distribuee'], true)) {
                $invitation->status = 'ouverte';
            }
            $invitation->save();
        }

        $rsvp = $invitation->rsvp;
        $qr = ($rsvp && $rsvp->response === 'accepte')
            ? $this->qrSvg($invitation->qr_token)
            : null;

        $mapsUrl = $event->map_url
            ?: ($event->address
                ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(trim($event->venue_name.' '.$event->address))
                : null);

        return view('invitation.show', [
            'invitation' => $invitation,
            'event' => $event,
            'guest' => $invitation->guest,
            'rsvp' => $rsvp,
            'qr' => $qr,
            'mapsUrl' => $mapsUrl,
            'jour' => EventOptions::date($event, 'l j F Y'),
            'heure' => EventOptions::date($event, 'H \h i'),
            'typeLabel' => EventOptions::typeLabel($event->type),
        ]);
    }

    /** §17 : réponse de l'invité. Il peut la modifier en renvoyant le formulaire. */
    public function rsvp(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->find($token);
        $event = $invitation->event;
        $guest = $invitation->guest;
        $settings = $event->rsvp_settings ?? [];
        $asked = fn (string $key) => ! empty($settings[$key]);
        $places = max(1, (int) $guest->allowed_seats);

        $data = $request->validate([
            'response' => 'required|in:accepte,refuse',
            'party_size' => "nullable|integer|min:1|max:$places",
            'companion_name' => 'nullable|string|max:255',
            'dietary_preferences' => 'nullable|string|max:255',
            'special_needs' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:1000',
        ]);

        $accepte = $data['response'] === 'accepte';

        Rsvp::updateOrCreate(
            ['invitation_id' => $invitation->id],
            [
                'response' => $data['response'],
                'party_size' => $accepte
                    ? ($asked('ask_party_size') ? ($data['party_size'] ?? $places) : $places)
                    : 0,
                'companion_name' => $accepte && $asked('ask_companion') ? ($data['companion_name'] ?? null) : null,
                'dietary_preferences' => $accepte && $asked('ask_diet') ? ($data['dietary_preferences'] ?? null) : null,
                'special_needs' => $accepte && $asked('ask_special_needs') ? ($data['special_needs'] ?? null) : null,
                'comment' => $asked('ask_comment') ? ($data['comment'] ?? null) : null,
                'responded_at' => now(),
            ]
        );

        // §18 : statut de l'invitation (on ne touche pas à « Arrivée »)
        if ($invitation->status !== 'arrivee') {
            $invitation->status = $accepte ? 'confirmee' : 'refusee';
            $invitation->save();
        }

        return redirect()->route('invitation.show', $token)->with('merci', $accepte
            ? 'Merci ! Votre présence est confirmée.'
            : 'Merci, votre réponse a bien été enregistrée.');
    }

    /** §28 : ajout au calendrier (fichier .ics). */
    public function calendar(string $token)
    {
        $invitation = $this->find($token);
        $event = $invitation->event;

        $debut = $event->starts_at->copy()->utc();
        $fin = ($event->ends_at ?? $event->starts_at->copy()->addHours(3))->copy()->utc();
        $lieu = trim(($event->venue_name ?? '').' '.($event->address ?? ''));

        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Newal//FR\r\nBEGIN:VEVENT\r\n"
            ."UID:{$invitation->code}@newal\r\n"
            .'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z')."\r\n"
            .'DTSTART:'.$debut->format('Ymd\THis\Z')."\r\n"
            .'DTEND:'.$fin->format('Ymd\THis\Z')."\r\n"
            .'SUMMARY:'.$this->ics($event->name)."\r\n"
            .($lieu !== '' ? 'LOCATION:'.$this->ics($lieu)."\r\n" : '')
            ."END:VEVENT\r\nEND:VCALENDAR\r\n";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="invitation.ics"',
        ]);
    }

    // ------------------------------------------------------------------

    private function find(string $token): Invitation
    {
        $invitation = Invitation::with(['event.programItems', 'guest', 'rsvp'])
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($invitation->guest && $invitation->event && $invitation->event->status !== 'archive', 404);

        return $invitation;
    }

    /** §20 : le QR Code ne contient que le token sécurisé. */
    private function qrSvg(string $data): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(240, 1), new SvgImageBackEnd()));

        return preg_replace('/^<\?xml.*?\?>\s*/s', '', $writer->writeString($data));
    }

    private function ics(string $text): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $text);
    }
}