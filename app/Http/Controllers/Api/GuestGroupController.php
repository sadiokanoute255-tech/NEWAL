<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesEvent;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\GuestGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestGroupController extends Controller
{
    use AuthorizesEvent;

    public function index(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);

        return $event->groups()->withCount('guests')->orderBy('name')->get();
    }

    public function store(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100',
                Rule::unique('guest_groups', 'name')->where('event_id', $event->id)],
            'color' => 'nullable|string|max:9',
        ]);

        return response()->json($event->groups()->create($data), 201);
    }

    public function update(Request $request, GuestGroup $group)
    {
        $this->ensureCanWrite($request, $group->event);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100',
                Rule::unique('guest_groups', 'name')
                    ->where('event_id', $group->event_id)
                    ->ignore($group->id)],
            'color' => 'nullable|string|max:9',
        ]);
        $group->update($data);

        return $group;
    }

    public function destroy(Request $request, GuestGroup $group)
    {
        $this->ensureCanWrite($request, $group->event);
        $group->delete(); // les invités du cercle passent à "sans cercle"

        return response()->json(['message' => 'Cercle supprimé.']);
    }
}
