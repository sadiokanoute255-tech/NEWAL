<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Invitation;
use App\Support\EventOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index(Request $request, Event $event)
    {
        abort_unless($request->user()->canReadOrg($event->organization_id), 403, 'Accès refusé.');

        $guests = $event->guests()
            ->with(['group:id,name,color', 'invitation.rsvp'])
            ->when($request->search, function ($q, $s) {
                $q->where(function ($w) use ($s) {
                    $w->where('first_name', 'like', "%{$s}%")
                      ->orWhere('last_name', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        $invitations = fn () => Invitation::where('event_id', $event->id)->whereHas('guest');

        return view('espace.invitations.index', [
            'event' => $event,
            'guests' => $guests,
            'missing' => $event->guests()->doesntHave('invitation')->count(),
            'total' => $invitations()->count(),
            'sent' => $invitations()->whereNotNull('sent_at')->count(),
            'canWrite' => $request->user()->canManageOrg($event->organization_id),
        ]);
    }

    /** §13 : une invitation (code, lien et QR uniques) pour chaque invité. */
    public function generate(Request $request, Event $event): RedirectResponse
    {
        $this->ensureCanWrite($request, $event);

        $created = 0;
        foreach ($event->guests()->doesntHave('invitation')->get() as $guest) {
            Invitation::create([
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'code' => $this->uniqueCode(),
                'token' => $this->uniqueToken('token', 40),
                'qr_token' => $this->uniqueToken('qr_token', 48),
                'status' => 'creee',
                'open_count' => 0,
            ]);
            $created++;
        }

        return back()->with('succes', $created > 0
            ? "$created invitation(s) générée(s)."
            : 'Tous vos invités ont déjà une invitation.');
    }

    /** §15.1 : message WhatsApp prérempli avec le lien sécurisé. */
    public function whatsapp(Request $request, Invitation $invitation)
    {
        $event = $invitation->event;
        $this->ensureCanWrite($request, $event);
        $guest = $invitation->guest;
        abort_unless($guest, 404);

        $date = EventOptions::date($event);
        $lieu = $event->venue_name ? ' à '.$event->venue_name : '';

        $message = "Bonjour {$guest->display_name},\n\n"
            ."Vous êtes invité(e) à « {$event->name} » le {$date}{$lieu}.\n\n"
            ."Confirmez votre présence et retrouvez votre QR Code ici :\n"
            .$invitation->public_url;

        // §18 : l'invitation passe de « Créée » à « Envoyée »
        if ($invitation->status === 'creee') {
            $invitation->status = 'envoyee';
        }
        $invitation->sent_at = $invitation->sent_at ?? now();
        $invitation->last_channel = 'whatsapp';
        $invitation->save();

        $numero = $this->whatsappNumber($guest->whatsapp ?: $guest->phone);
        $base = $numero ? "https://wa.me/{$numero}" : 'https://wa.me/';

        return redirect()->away($base.'?text='.rawurlencode($message));
    }

    // ------------------------------------------------------------------

    /** Chiffres uniquement ; un numéro sénégalais à 9 chiffres reçoit l'indicatif 221. */
    private function whatsappNumber(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            $digits = '221'.$digits;
        }

        return $digits;
    }

    private function uniqueCode(): string
    {
        do {
            $code = 'NWL-'.strtoupper(Str::random(8));
        } while (Invitation::where('code', $code)->exists());

        return $code;
    }

    private function uniqueToken(string $column, int $length): string
    {
        do {
            $token = Str::random($length);
        } while (Invitation::where($column, $token)->exists());

        return $token;
    }
    private function ensureCanWrite(Request $request, Event $event): void
    {
        abort_unless($request->user()->canSendInvitations($event->organization_id), 403, 'Droits insuffisants.');
    }
}