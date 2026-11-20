<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Équipe de l'organisation : collaborateurs et agents d'accueil (§4.3, §4.4, §40). Réservée au propriétaire. */
class TeamController extends Controller
{
    public function index(Request $request)
    {
        $org = $this->organisation($request);

        $membres = $org->users()->withPivot('role', 'title', 'all_events')->orderBy('name')->get();

        $acces = DB::table('event_access')
            ->whereIn('user_id', $membres->pluck('id'))
            ->get()
            ->groupBy('user_id')
            ->map(fn ($lignes) => $lignes->pluck('event_id')->all());

        return view('espace.equipe.index', [
            'org' => $org,
            'membres' => $membres,
            'acces' => $acces,
            'events' => Event::where('organization_id', $org->id)
                ->where('status', '!=', 'archive')
                ->orderByDesc('starts_at')
                ->get(['id', 'name']),
            'activite' => ActivityLog::with('user:id,name,first_name')
                ->whereIn('user_id', $membres->pluck('id'))
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $org = $this->organisation($request);

        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30|unique:users,phone',
            'title' => 'nullable|string|max:60',
            'role' => ['required', Rule::in(Roles::ATTRIBUABLES)],
                        'password' => ['required', 'string', new MotDePasseFort],
        ] + $this->reglesPerimetre($org));

        if ($erreur = $this->verifierPerimetre($data)) {
            return back()->withInput()->withErrors(['events' => $erreur]);
        }

        // Le type de compte suit celui de l'organisation (la base n'accepte qu'une liste fermée de valeurs).
        // Le rôle réel du collaborateur est stocké dans l'organisation.
        $user = User::create([
            'name' => $data['name'],
            'first_name' => $data['first_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'password' => $data['password'],
            'account_type' => $request->user()->account_type,
            'locale' => 'fr',
        ]);

        $user->organizations()->attach($org->id, [
            'role' => $data['role'],
            'title' => $data['title'] ?: null,
            'all_events' => $data['scope'] === 'tous',
        ]);

        $this->syncAcces($org, $user, $data['scope'], $data['events'] ?? []);

        $this->journal($request, 'creation', "Accès créé pour {$user->first_name} {$user->name} : ".Roles::LIBELLES[$data['role']].$this->libellePerimetre($data));

        return back()->with('succes', "Accès créé pour {$user->first_name} {$user->name}. Transmettez-lui son e-mail et le mot de passe provisoire : il pourra le changer dans « Mon profil ».");
    }

    public function update(Request $request, User $membre): RedirectResponse
    {
        $org = $this->organisation($request);
        $this->verifierMembre($org, $membre);

        $data = $request->validate([
            'role' => ['required', Rule::in(Roles::ATTRIBUABLES)],
            'title' => 'nullable|string|max:60',
                        'password' => ['nullable', 'string', new MotDePasseFort],
        ] + $this->reglesPerimetre($org));

        if ($erreur = $this->verifierPerimetre($data)) {
            return back()->withInput()->withErrors(['events' => $erreur]);
        }

        $membre->organizations()->updateExistingPivot($org->id, [
            'role' => $data['role'],
            'title' => $data['title'] ?: null,
            'all_events' => $data['scope'] === 'tous',
        ]);

        $this->syncAcces($org, $membre, $data['scope'], $data['events'] ?? []);

        if (! empty($data['password'])) {
            $membre->update(['password' => $data['password']]);
        }

        $this->journal($request, 'modification', "Accès de {$membre->first_name} {$membre->name} modifié : ".Roles::LIBELLES[$data['role']].$this->libellePerimetre($data));

        return back()->with('succes', 'Accès mis à jour.');
    }

    public function destroy(Request $request, User $membre): RedirectResponse
    {
        $org = $this->organisation($request);
        $this->verifierMembre($org, $membre);

        $this->syncAcces($org, $membre, 'tous', []); // efface les affectations de cette organisation
        $membre->organizations()->detach($org->id);

        // Un compte qui n'appartient plus à aucune organisation est suspendu :
        // il reste dans l'historique mais ne peut plus se connecter.
        if (! $membre->is_admin && $membre->organizations()->count() === 0) {
            $membre->forceFill(['is_suspended' => true])->save();
            $membre->tokens()->delete();
        }

        $this->journal($request, 'suppression', "Accès retiré à {$membre->first_name} {$membre->name}");

        return back()->with('succes', 'Accès retiré.');
    }

    // ------------------------------------------------------------------

    private function organisation(Request $request): Organization
    {
        $org = $request->user()->ownedOrganization();
        abort_unless($org, 403, 'Réservé au propriétaire de l\'organisation.');

        return $org;
    }

    /** Le membre doit appartenir à l'organisation et ne pas en être le propriétaire. */
    private function verifierMembre(Organization $org, User $membre): void
    {
        $role = $membre->roleIn($org->id);
        abort_if($role === null || $role === 'proprietaire', 404);
    }

    private function reglesPerimetre(Organization $org): array
    {
        return [
            'scope' => 'required|in:tous,certains',
            'events' => 'nullable|array',
            'events.*' => ['integer', Rule::exists('events', 'id')->where('organization_id', $org->id)],
        ];
    }

    private function verifierPerimetre(array $data): ?string
    {
        return ($data['scope'] === 'certains' && empty($data['events']))
            ? 'Choisissez au moins un événement, ou donnez l\'accès à tous les événements.'
            : null;
    }

    private function libellePerimetre(array $data): string
    {
        return $data['scope'] === 'tous'
            ? ' (tous les événements)'
            : ' ('.count($data['events'] ?? []).' événement(s))';
    }

    /** Remplace les événements affectés à la personne dans cette organisation. */
    private function syncAcces(Organization $org, User $user, string $scope, array $ids): void
    {
        DB::table('event_access')
            ->where('user_id', $user->id)
            ->whereIn('event_id', DB::table('events')->where('organization_id', $org->id)->select('id'))
            ->delete();

        if ($scope === 'certains' && $ids !== []) {
            $now = now();
            DB::table('event_access')->insert(collect($ids)->unique()->map(fn ($id) => [
                'user_id' => $user->id,
                'event_id' => (int) $id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }
    }

    private function journal(Request $request, string $action, string $description): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => mb_substr($description, 0, 250),
        ]);
    }
}