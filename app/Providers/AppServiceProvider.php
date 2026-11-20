<?php
namespace App\Providers;
use App\Models\ActivityLog;
use App\Models\CheckIn;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
                $this->journal();
    }
    
    /**
     * Historique des actions (§39) : chaque action d'un utilisateur connecté est notée.
     * Les actions des invités (réponse, ouverture) n'ont pas d'utilisateur et ne sont pas notées.
     */
    private function journal(): void
    {
        $log = function (string $action, string $description, ?int $eventId = null): void {
            if (! auth()->check()) {
                return;
            }
            ActivityLog::create([
                'user_id' => auth()->id(),
                'event_id' => $eventId,
                'action' => $action,
                'description' => mb_substr($description, 0, 250),
            ]);
        };

        $modifie = fn ($modele) => count(array_diff(array_keys($modele->getChanges()), ['updated_at'])) > 0;

        Event::created(fn ($e) => $log('creation', "Événement « {$e->name} » créé", $e->id));
        Event::updated(function ($e) use ($log, $modifie) {
            if ($e->wasChanged('status')) {
                $log('statut', "Événement « {$e->name} » : statut « {$e->status} »", $e->id);
            } elseif ($e->wasChanged('plan_id')) {
                $log('modification', "Offre de l'événement « {$e->name} » modifiée", $e->id);
            } elseif ($modifie($e)) {
                $log('modification', "Événement « {$e->name} » modifié", $e->id);
            }
        });
        Event::deleted(fn ($e) => $log('suppression', "Événement « {$e->name} » supprimé", $e->id));

        Guest::deleted(fn ($g) => $log('suppression', "Invité supprimé : {$g->display_name}", $g->event_id));

        Invitation::updated(function ($i) use ($log) {
            if ($i->wasChanged('sent_at') && $i->sent_at) {
                $log('envoi', 'Invitation envoyée à '.($i->guest?->display_name ?? 'un invité'), $i->event_id);
            }
        });

        CheckIn::created(fn ($c) => $log(
            'scan',
            'Entrée de '.$c->persons_entered.' personne(s) : '.($c->invitation?->guest?->display_name ?? 'un invité'),
            $c->event_id
        ));
        CheckIn::updated(function ($c) use ($log) {
            if ($c->wasChanged('cancelled_at') && $c->cancelled_at) {
                $log('statut', 'Entrée annulée : '.($c->invitation?->guest?->display_name ?? 'un invité'), $c->event_id);
            }
        });

        Plan::created(fn ($p) => $log('creation', "Offre « {$p->name} » créée"));
        Plan::updated(function ($p) use ($log, $modifie) {
            if ($modifie($p)) {
                $log('modification', "Offre « {$p->name} » modifiée");
            }
        });

        User::updated(function ($u) use ($log) {
            if ($u->wasChanged('is_suspended')) {
                $nom = trim(($u->first_name ?? '').' '.$u->name);
                $log('statut', "Compte de {$nom} ".($u->is_suspended ? 'suspendu' : 'réactivé'));
            }
        });

        Complaint::created(fn ($c) => $log('creation', "Réclamation envoyée : {$c->subject}", $c->event_id));
        Complaint::updated(function ($c) use ($log) {
            if ($c->wasChanged('status')) {
                $log('statut', "Réclamation « {$c->subject} » : ".(Complaint::STATUS[$c->status] ?? $c->status), $c->event_id);
            }
        });
    }
}
