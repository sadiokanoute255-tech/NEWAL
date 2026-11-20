<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\Request;

/** Pages du menu (invités, invitations, contrôle d'accès, statistiques) : choix de l'événement. */
class SectionController extends Controller
{
    public function invites(Request $request)
    {
        return $this->choisir($request, 'invites');
    }

    public function invitations(Request $request)
    {
        return $this->choisir($request, 'invitations');
    }

    public function controle(Request $request)
    {
        return $this->choisir($request, 'controle');
    }

    public function statistiques(Request $request)
    {
        return $this->choisir($request, 'statistiques');
    }

    private function choisir(Request $request, string $section)
    {
            if ($request->user()->mainRole() === 'accueil' && $section !== 'controle') {
            return redirect()->route('section.controle');
        } 
            $events = Event::accessibleBy($request->user())
            ->where('status', '!=', 'archive')
            ->withCount('guests')
            ->orderByDesc('starts_at')
            ->get();

        // Un seul événement : on va directement dedans.
        if ($events->count() === 1) {
            return redirect()->route($section.'.index', $events->first());
        }

        $stats = Invitation::whereIn('event_id', $events->pluck('id'))
            ->whereHas('guest')
            ->selectRaw('event_id, count(*) as total, sum(case when sent_at is not null then 1 else 0 end) as sent')
            ->groupBy('event_id')
            ->get()
            ->keyBy('event_id');

        $titres = [
            'invites' => 'Mes invités',
            'invitations' => 'Invitations',
            'controle' => 'Contrôle d\'accès',
            'statistiques' => 'Statistiques',
        ];
        $aides = [
            'invites' => 'Choisissez un événement pour gérer ses invités.',
            'invitations' => 'Choisissez un événement pour générer et envoyer ses invitations.',
            'controle' => 'Choisissez l\'événement dont vous contrôlez l\'entrée.',
            'statistiques' => 'Choisissez un événement pour voir ses statistiques.',
        ];

        return view('espace.choisir', [
            'section' => $section,
            'events' => $events,
            'stats' => $stats,
            'titre' => $titres[$section],
            'aide' => $aides[$section],
        ]);
    }
}