<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\EventOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
                abort_if($request->user()->mainRole() === 'accueil', 403, 'Accès refusé.');
                $events = Event::accessibleBy($request->user())
            ->when($request->status,
                fn ($q, $s) => $q->where('status', $s),
                fn ($q) => $q->where('status', '!=', 'archive'))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->withCount('guests')
            ->orderByDesc('starts_at')
            ->paginate(12)
            ->withQueryString();

        return view('espace.evenements.index', compact('events'));
    }

    public function create(Request $request)
    {
        $orgs = $this->manageableOrgs($request);
        abort_if($orgs->isEmpty(), 403, 'Droits insuffisants.');

        return view('espace.evenements.create', ['event' => null, 'orgs' => $orgs]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge($this->prepare($request));

        $data = $request->validate(array_merge(
            ['organization_id' => 'required|exists:organizations,id'],
            $this->rules()
        ));

        abort_unless($request->user()->canManageOrg((int) $data['organization_id']), 403, 'Droits insuffisants.');

        $event = DB::transaction(function () use ($data, $request) {
            $program = $data['program'] ?? [];
            unset($data['program']);

            // Visuel principal : le fichier est rangé dans storage/app/public/covers
            $cover = $data['cover'] ?? null;
            unset($data['cover'], $data['remove_cover']);
            if ($cover) {
                $data['cover_path'] = $cover->store('covers', 'public');
            }

            $data = $this->convertDates($data);
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(6));
            $data['created_by'] = $request->user()->id;
            $data['status'] = 'brouillon';

                       $event = Event::create($data);

            // Un gestionnaire limité à certains événements garde l'accès à ceux qu'il crée
            if (! $request->user()->hasFullAccess((int) $data['organization_id'])) {
                DB::table('event_access')->insert([
                    'user_id' => $request->user()->id,
                    'event_id' => $event->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->syncProgram($event, $program);

            return $event;
        });

        return redirect()->route('evenements.show', $event)->with('succes', 'Événement créé.');
    }

    public function show(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);
        $event->load('programItems');

        return view('espace.evenements.show', [
            'event' => $event,
            'guestsCount' => $event->guests()->count(),
            'guestLimit' => $event->guestLimit(),
            'canWrite' => $request->user()->canManageOrg($event->organization_id),
        ]);
    }

    public function edit(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);
        $event->load('programItems');

        return view('espace.evenements.edit', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->ensureCanWrite($request, $event);
        $request->merge($this->prepare($request));

        $data = $request->validate($this->rules());

        DB::transaction(function () use ($event, $data, $request) {
            $program = $data['program'] ?? [];
            unset($data['program']);

            // Visuel principal : remplacé, supprimé ou laissé tel quel
            $cover = $data['cover'] ?? null;
            unset($data['cover'], $data['remove_cover']);
            if ($cover) {
                if ($event->cover_path) {
                    Storage::disk('public')->delete($event->cover_path);
                }
                $data['cover_path'] = $cover->store('covers', 'public');
            } elseif ($request->boolean('remove_cover') && $event->cover_path) {
                Storage::disk('public')->delete($event->cover_path);
                $data['cover_path'] = null;
            }

            $event->update($this->convertDates($data));
            $this->syncProgram($event, $program);
        });

        return redirect()->route('evenements.show', $event)->with('succes', 'Modifications enregistrées.');
    }

    public function publish(Request $request, Event $event): RedirectResponse
    {
        $this->ensureCanWrite($request, $event);
        $event->update(['status' => 'publie']);

        return back()->with('succes', 'Événement publié.');
    }

    public function duplicate(Request $request, Event $event): RedirectResponse
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

        return redirect()->route('evenements.edit', $copy)
            ->with('succes', 'Événement dupliqué (sans les invités). Vérifiez les dates.');
    }

    /** §46 : on archive au lieu de supprimer. */
    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->ensureCanWrite($request, $event);
        $event->update(['status' => 'archive']);

        return redirect()->route('evenements.index')->with('succes', 'Événement archivé.');
    }

    // ------------------------------------------------------------------

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'timezone' => 'required|timezone',
            'venue_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'map_url' => 'nullable|url|max:500',
            'dress_code' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'contact_name' => 'nullable|string|max:150',
            'contact_phone' => 'nullable|string|max:30',
            'contact_email' => 'nullable|email|max:150',
            'website' => 'nullable|url|max:255',
            'visibility' => 'required|in:'.implode(',', array_keys(EventOptions::VISIBILITY)),
            'rsvp_settings' => 'required|array',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_cover' => 'nullable|boolean',
            'program' => 'nullable|array',
            'program.*.time' => 'required|date_format:H:i',
            'program.*.title' => 'required|string|max:255',
            'program.*.description' => 'nullable|string',
        ];
    }

    /** Nettoie le programme (lignes vides ignorées) et construit les réglages RSVP. */
    private function prepare(Request $request): array
    {
        $program = collect($request->input('program', []))
            ->filter(fn ($row) => filled($row['time'] ?? null) || filled($row['title'] ?? null))
            ->values()
            ->all();

        $rsvp = [];
        foreach (array_keys(EventOptions::RSVP) as $key) {
            $rsvp[$key] = $request->boolean("rsvp.$key");
        }

        return ['program' => $program, 'rsvp_settings' => $rsvp];
    }

    private function convertDates(array $data): array
    {
        $tz = $data['timezone'];
        $data['starts_at'] = EventOptions::toApp($data['starts_at'], $tz);
        $data['ends_at'] = ! empty($data['ends_at']) ? EventOptions::toApp($data['ends_at'], $tz) : null;

        return $data;
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

    private function manageableOrgs(Request $request)
    {
        $user = $request->user();

        return $user->organizations()->get()->filter(fn ($o) => $user->canManageOrg($o->id))->values();
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