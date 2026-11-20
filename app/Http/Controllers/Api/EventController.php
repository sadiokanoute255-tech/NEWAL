<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /** Liste des événements de toutes les organisations de l'utilisateur. */
    public function index(Request $request)
    {
        $orgIds = $request->user()->organizations()->pluck('organizations.id');

        $events = Event::whereIn('organization_id', $orgIds)
            ->when($request->status,
                fn ($q, $s) => $q->where('status', $s),
                fn ($q) => $q->where('status', '!=', 'archive'))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderByDesc('starts_at')
            ->paginate(15);

        return $events;
    }

    public function store(Request $request)
    {
        $data = $request->validate(array_merge(
            ['organization_id' => 'required|exists:organizations,id'],
            $this->rules(false)
        ));

        abort_unless($request->user()->canManageOrg((int) $data['organization_id']), 403, 'Droits insuffisants.');

        $event = DB::transaction(function () use ($data, $request) {
            $program = $data['program'] ?? [];
            unset($data['program']);

            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(6));
            $data['created_by'] = $request->user()->id;
            $data['status'] = 'brouillon';
            $data['rsvp_settings'] = $data['rsvp_settings'] ?? [
                'ask_party_size' => true,
                'ask_companion' => false,
                'ask_diet' => false,
                'ask_special_needs' => false,
                'ask_comment' => true,
            ];

            $event = Event::create($data);
            $this->syncProgram($event, $program);

            return $event;
        });

        return response()->json($event->load('programItems'), 201);
    }

    public function show(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);

        return $event->load('programItems');
    }

    public function update(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);

        $data = $request->validate($this->rules(true));

        DB::transaction(function () use ($event, $data) {
            $program = $data['program'] ?? null;
            unset($data['program']);

            $event->update($data);
            if ($program !== null) {
                $this->syncProgram($event, $program);
            }
        });

        return $event->fresh()->load('programItems');
    }

    /** §46 : on archive plutôt que de supprimer. */
    public function destroy(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);
        $event->update(['status' => 'archive']);

        return response()->json(['message' => 'Événement archivé.']);
    }

    public function publish(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);
        $event->update(['status' => 'publie']);

        return $event;
    }

    /** §47 : duplication sans les invités. */
    public function duplicate(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);

        $copy = DB::transaction(function () use ($event, $request) {
            $copy = $event->replicate();
            $copy->name = $event->name.' (copie)';
            $copy->slug = Str::slug($event->name).'-'.Str::lower(Str::random(6));
            $copy->status = 'brouillon';
            $copy->created_by = $request->user()->id;
            $copy->save();

            foreach ($event->programItems as $item) {
                $copy->programItems()->create($item->only(['time', 'title', 'description', 'sort_order']));
            }

            return $copy;
        });

        return response()->json($copy->load('programItems'), 201);
    }

    // ---------------------------------------------------------------

    private function rules(bool $update): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'timezone' => 'nullable|timezone',
            'venue_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'map_url' => 'nullable|url|max:500',
            'dress_code' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'contact_name' => 'nullable|string|max:150',
            'contact_phone' => 'nullable|string|max:30',
            'contact_email' => 'nullable|email|max:150',
            'website' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'visibility' => 'nullable|in:prive,sur_invitation,public_inscription',
            'rsvp_settings' => 'nullable|array',
            'invitation_design' => 'nullable|array',
            'program' => 'nullable|array',
            'program.*.time' => 'required|date_format:H:i',
            'program.*.title' => 'required|string|max:255',
            'program.*.description' => 'nullable|string',
        ];

        if ($update) {
            // En modification, tous les champs deviennent optionnels
            $rules = collect($rules)->map(
                fn ($r, $k) => str_starts_with($k, 'program.') ? $r : 'sometimes|'.$r
            )->all();
        }

        return $rules;
    }

    private function syncProgram(Event $event, array $program): void
    {
        $event->programItems()->delete();
        foreach (array_values($program) as $i => $item) {
            $event->programItems()->create([
                'time' => $item['time'],
                'title' => $item['title'],
                'description' => $item['description'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    private function ensureCanRead(Request $request, Event $event): void
    {
        abort_unless($request->user()->roleIn($event->organization_id), 403, 'Accès refusé.');
    }

    private function ensureCanWrite(Request $request, Event $event): void
    {
        abort_unless($request->user()->canManageOrg($event->organization_id), 403, 'Droits insuffisants.');
    }
}
