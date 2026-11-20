<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Administration ATAABA (§4.1, §37). Réservée aux administrateurs. */
class AdminController extends Controller
{
    /** §37.1 : tableau de bord. */
    public function dashboard(Request $request)
    {
        $this->ensureAdmin($request);

        $invitations = fn () => Invitation::whereHas('guest');

        $stats = [
            'users' => User::count(),
            'new_users' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'suspended' => User::where('is_suspended', true)->count(),
            'events' => Event::count(),
            'upcoming' => Event::where('status', '!=', 'archive')->where('starts_at', '>=', now())->count(),
            'guests' => Guest::count(),
            'invitations' => $invitations()->count(),
            'sent' => $invitations()->whereNotNull('sent_at')->count(),
            'with_plan' => Event::whereNotNull('plan_id')->count(),
        ];

        return view('espace.admin.tableau', [
            'stats' => $stats,
            'newUsers' => User::latest()->limit(6)->get(),
            'lastEvents' => Event::with('organization:id,name')->withCount('guests')->latest()->limit(6)->get(),
        ]);
    }

    /** §37.2 : gestion des utilisateurs. */
    public function users(Request $request)
    {
        $this->ensureAdmin($request);

        $users = User::with('organizations:id,name')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                      ->orWhere('first_name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('espace.admin.utilisateurs', ['users' => $users]);
    }

    /** §4.1 : suspendre ou réactiver un compte. */
    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_if($user->id === $request->user()->id, 422, 'Vous ne pouvez pas suspendre votre propre compte.');

        $suspendre = ! $user->is_suspended;
        $user->forceFill(['is_suspended' => $suspendre])->save();

        if ($suspendre) {
            $user->tokens()->delete(); // coupe aussi l'accès par l'API
        }

        $nom = trim(($user->first_name ?? '').' '.$user->name);

        return back()->with('succes', $suspendre ? "Le compte de {$nom} est suspendu." : "Le compte de {$nom} est réactivé.");
    }

    public function plans(Request $request)
    {
        $this->ensureAdmin($request);

        return view('espace.admin.offres', [
            'plans' => Plan::orderBy('sort_order')->orderBy('max_guests')->get(),
        ]);
    }

    public function storePlan(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        Plan::create($data);

        return back()->with('succes', 'Offre créée.');
    }

    public function updatePlan(Request $request, Plan $plan): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $plan->update($data);

        return back()->with('succes', "Offre « {$plan->name} » enregistrée.");
    }

    public function events(Request $request)
    {
        $this->ensureAdmin($request);

        $events = Event::with(['organization:id,name', 'plan:id,name'])
            ->withCount('guests')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('espace.admin.evenements', [
            'events' => $events,
            'plans' => Plan::orderBy('sort_order')->orderBy('max_guests')->get(),
        ]);
    }

    public function assignPlan(Request $request, Event $event): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate(['plan_id' => 'nullable|exists:plans,id']);
        $plan = ! empty($data['plan_id']) ? Plan::find($data['plan_id']) : null;
        $limit = $plan?->max_guests ?? Event::DEFAULT_GUEST_LIMIT;

        if ($event->guests()->count() > $limit) {
            return back()->withErrors(['plan_id' => "« {$event->name} » a déjà plus de {$limit} invités : offre non appliquée."]);
        }

        $event->update(['plan_id' => $plan?->id]);

        return back()->with('succes', "Offre de « {$event->name} » : ".($plan?->name ?? 'par défaut').'.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'max_guests' => 'required|integer|min:1|max:1000000',
            'price' => 'required|integer|min:0|max:1000000000',
            'currency' => 'required|string|max:5',
            'billing_period' => 'required|string|max:20',
            'sort_order' => 'required|integer|min:0|max:1000',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->is_admin, 403, 'Réservé aux administrateurs.');
    }
}