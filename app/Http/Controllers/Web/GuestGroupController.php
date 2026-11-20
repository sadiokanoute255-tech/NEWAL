<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\GuestGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestGroupController extends Controller
{
    public function index(Request $request, Event $event)
    {
        abort_unless($request->user()->roleIn($event->organization_id), 403, 'Accès refusé.');

        return view('espace.cercles.index', [
            'event' => $event,
            'groups' => $event->groups()->withCount('guests')->orderBy('name')->get(),
            'canWrite' => $request->user()->canManageOrg($event->organization_id),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->ensureCanWrite($request, $event);

        $data = $request->validate($this->rules($event));
        $event->groups()->create($data);

        return back()->with('succes', 'Cercle créé.');
    }

    public function update(Request $request, GuestGroup $group): RedirectResponse
    {
        $event = $group->event;
        $this->ensureCanWrite($request, $event);

        $group->update($request->validate($this->rules($event, $group)));

        return back()->with('succes', 'Cercle modifié.');
    }

    public function destroy(Request $request, GuestGroup $group): RedirectResponse
    {
        $this->ensureCanWrite($request, $group->event);

        // Les invités du cercle ne sont pas supprimés : ils deviennent « sans cercle ».
        $group->guests()->update(['guest_group_id' => null]);
        $group->delete();

        return back()->with('succes', 'Cercle supprimé.');
    }

    private function rules(Event $event, ?GuestGroup $ignore = null): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('guest_groups', 'name')->where('event_id', $event->id)->ignore($ignore?->id),
            ],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    private function ensureCanWrite(Request $request, Event $event): void
    {
        abort_unless($request->user()->canManageOrg($event->organization_id), 403, 'Droits insuffisants.');
    }
}