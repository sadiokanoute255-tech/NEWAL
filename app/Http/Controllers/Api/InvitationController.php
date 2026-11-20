<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesEvent;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    use AuthorizesEvent;

    /** Liste des invitations d'un événement (avec invité et réponse RSVP). */
    public function index(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);

        return Invitation::where('event_id', $event->id)
            ->whereHas('guest', fn ($q) => $q->when(
                $request->group_id,
                fn ($w, $g) => $w->where('guest_group_id', $g)
            ))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($w) use ($s) {
                    $w->where('code', 'like', "%{$s}%")
                      ->orWhereHas('guest', function ($g) use ($s) {
                          $g->where('first_name', 'like', "%{$s}%")
                            ->orWhere('last_name', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%");
                      });
                });
            })
            ->with([
                'guest:id,first_name,last_name,phone,whatsapp,allowed_seats,guest_group_id',
                'guest.group:id,name,color',
                'rsvp',
            ])
            ->orderByDesc('id')
            ->paginate(50);
    }

    /** Crée les invitations manquantes (invités ajoutés avant l'activation du module). */
    public function generate(Request $request, Event $event)
    {
        $this->ensureCanSend($request, $event);

        $created = 0;
        foreach ($event->guests()->whereDoesntHave('invitation')->get() as $guest) {
            Invitation::createFor($guest);
            $created++;
        }

        return [
            'created' => $created,
            'total' => Invitation::where('event_id', $event->id)->count(),
        ];
    }

    /** Lien WhatsApp prérempli avec le lien sécurisé de l'invitation (§14-15 MVP). */
    public function whatsapp(Request $request, Invitation $invitation)
    {
        $event = $invitation->event;
        $guest = $invitation->guest;
        abort_if(! $event || ! $guest, 404);

        $this->ensureCanSend($request, $event);

        if ($event->status !== 'publie') {
            abort(422, "Publiez l'événement avant d'envoyer les invitations.");
        }

        $date = $event->starts_at->copy()
            ->timezone($event->timezone ?: 'Africa/Dakar')
            ->locale('fr')
            ->translatedFormat('l j F Y \à H\hi');

        $message = "Bonjour {$guest->display_name}, vous êtes invité(e) à « {$event->name} » le {$date}"
            .($event->venue_name ? " à {$event->venue_name}" : '')
            .".\nConfirmez votre présence ici : {$invitation->public_url}";

        $number = $this->waNumber($guest->whatsapp ?: $guest->phone);
        $url = 'https://wa.me/'.($number ?? '').'?text='.rawurlencode($message);

        return [
            'phone' => $number,
            'message' => $message,
            'whatsapp_url' => $url,
            'public_url' => $invitation->public_url,
        ];
    }

    /** À appeler quand l'organisateur a effectivement partagé l'invitation. */
    public function markSent(Request $request, Invitation $invitation)
    {
        $event = $invitation->event;
        abort_if(! $event, 404);
        $this->ensureCanSend($request, $event);

        $data = $request->validate(['channel' => 'required|in:whatsapp,sms,email,lien']);

        DB::transaction(function () use ($invitation, $event, $data, $request) {
            $updates = [
                'last_channel' => $data['channel'],
                'sent_at' => $invitation->sent_at ?? now(),
            ];
            // On ne rétrograde jamais un statut plus avancé (ouverte, confirmée...)
            if ($invitation->status === 'creee') {
                $updates['status'] = 'envoyee';
            }
            $invitation->update($updates);

            Message::create([
                'event_id' => $event->id,
                'invitation_id' => $invitation->id,
                'sent_by' => $request->user()->id,
                'channel' => $data['channel'],
                'type' => 'invitation',
                'status' => 'sent',
            ]);
        });

        return $invitation->fresh();
    }

    // ---------------------------------------------------------------

    /** Propriétaire, gestionnaire et communication peuvent envoyer des invitations. */
    private function ensureCanSend(Request $request, Event $event): void
    {
        $role = $request->user()->roleIn($event->organization_id);
        abort_unless(in_array($role, ['proprietaire', 'gestionnaire', 'communication'], true), 403, 'Droits insuffisants.');
    }

    /** Numéro au format international sans "+" (exigé par wa.me). */
    private function waNumber(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $phone = trim($phone);
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($phone, '+')) {
            return $digits;
        }
        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }
        $cc = (string) config('newal.default_country_code');
        if (str_starts_with($digits, $cc) && strlen($digits) > 9) {
            return $digits;
        }

        return $cc.ltrim($digits, '0');
    }
}
