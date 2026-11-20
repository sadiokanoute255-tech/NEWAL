<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);

        $guests = $event->guests()
            ->with(['group:id,name,color', 'invitation.rsvp'])
            ->when($request->group_id, fn ($q, $g) => $q->where('guest_group_id', $g))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($w) use ($s) {
                    $w->where('first_name', 'like', "%{$s}%")
                      ->orWhere('last_name', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%")
                      ->orWhere('organization', 'like', "%{$s}%");
                });
            })
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        return view('espace.invites.index', [
            'event' => $event,
            'guests' => $guests,
            'groups' => $event->groups()->orderBy('name')->get(),
            'total' => $event->guests()->count(),
            'limit' => $event->guestLimit(),
            'canWrite' => $request->user()->canManageOrg($event->organization_id),
        ]);
    }

    public function create(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);

        return view('espace.invites.create', [
            'event' => $event,
            'guest' => null,
            'groups' => $event->groups()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->ensureCanWrite($request, $event);

        if ($event->guests()->count() >= $event->guestLimit()) {
            return back()->withInput()->withErrors([
                'last_name' => "Quota d'invités atteint ({$event->guestLimit()}). Passez à une offre supérieure.",
            ]);
        }

        $data = $request->validate($this->rules($event));
        $data['allowed_seats'] = $data['allowed_seats'] ?? 1;

        $event->guests()->create($data);

        return redirect()->route('invites.index', $event)->with('succes', 'Invité ajouté.');
    }

    public function edit(Request $request, Guest $guest)
    {
        $event = $guest->event;
        $this->ensureCanWrite($request, $event);

        return view('espace.invites.edit', [
            'event' => $event,
            'guest' => $guest,
            'groups' => $event->groups()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Guest $guest): RedirectResponse
    {
        $event = $guest->event;
        $this->ensureCanWrite($request, $event);

        $data = $request->validate($this->rules($event, $guest));
        $data['allowed_seats'] = $data['allowed_seats'] ?? 1;
        $guest->update($data);

        return redirect()->route('invites.index', $event)->with('succes', 'Invité modifié.');
    }

    public function destroy(Request $request, Guest $guest): RedirectResponse
    {
        $event = $guest->event;
        $this->ensureCanWrite($request, $event);
        $guest->delete(); // suppression logique

        return redirect()->route('invites.index', $event)->with('succes', 'Invité supprimé.');
    }

    // ------------------------------------------------------------------

    private function rules(Event $event, ?Guest $ignore = null): array
    {
        return [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => [
                'nullable', 'string', 'max:30',
                Rule::unique('guests', 'phone')
                    ->where('event_id', $event->id)
                    ->whereNull('deleted_at')
                    ->ignore($ignore?->id),
            ],
            'whatsapp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'organization' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'guest_group_id' => [
                'nullable',
                Rule::exists('guest_groups', 'id')->where('event_id', $event->id),
            ],
            'allowed_seats' => 'nullable|integer|min:1|max:100',
            'comment' => 'nullable|string',
        ];
    }

    private function ensureCanRead(Request $request, Event $event): void
    {
        abort_unless($request->user()->canReadOrg($event->organization_id), 403, 'Accès refusé.');
    }

    private function ensureCanWrite(Request $request, Event $event): void
    {
        abort_unless($request->user()->canManageOrg($event->organization_id), 403, 'Droits insuffisants.');
    }
}