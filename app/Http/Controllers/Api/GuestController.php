<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesEvent;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    use AuthorizesEvent;

    public function index(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);

        return $event->guests()
            ->with('group:id,name,color')
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
            ->paginate(50);
    }

    public function store(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);

        if ($event->guests()->count() >= $event->guestLimit()) {
            abort(422, "Quota d'invités atteint ({$event->guestLimit()}). Passez à une offre supérieure.");
        }

        $data = $request->validate($this->rules($event));
        $data['allowed_seats'] = $data['allowed_seats'] ?? 1;

        $guest = $event->guests()->create($data);

        return response()->json($guest->load('group:id,name,color'), 201);
    }

    public function show(Request $request, Guest $guest)
    {
        $this->ensureCanRead($request, $guest->event);

        return $guest->load('group:id,name,color');
    }

    public function update(Request $request, Guest $guest)
    {
        $this->ensureCanWrite($request, $guest->event);

        $data = $request->validate($this->rules($guest->event, $guest));
        $guest->update($data);

        return $guest->fresh()->load('group:id,name,color');
    }

    public function destroy(Request $request, Guest $guest)
    {
        $this->ensureCanWrite($request, $guest->event);
        $guest->delete(); // suppression logique

        return response()->json(['message' => 'Invité supprimé.']);
    }

    /** Compteurs + quota pour l'affichage (ex. "32 / 50 invités"). */
    public function summary(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);

        return [
            'guests' => $event->guests()->count(),
            'seats' => (int) $event->guests()->sum('allowed_seats'),
            'limit' => $event->guestLimit(),
        ];
    }

    private function rules(Event $event, ?Guest $ignore = null): array
    {
        $update = $ignore !== null;
        $sometimes = $update ? 'sometimes|' : '';

        return [
            'first_name' => 'nullable|string|max:255',
            'last_name' => $sometimes.'required|string|max:255',
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
}
