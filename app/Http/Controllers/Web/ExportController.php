<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Support\EventOptions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

/** Rapports et exports (§31) : Excel, PDF et CSV. */
class ExportController extends Controller
{
    private const RAPPORTS = [
        'invites' => ['Liste complète des invités', 'Tous les invités avec leur cercle, leur statut, leur réponse et leurs entrées.'],
        'confirmations' => ['Confirmations', 'Les invités qui participeront, avec le nombre de personnes et leurs préférences.'],
        'refus' => ['Refus', 'Les invités qui ont indiqué ne pas pouvoir venir.'],
        'sans-reponse' => ['Sans réponse', 'Les invités qui n\'ont pas encore répondu : utile pour les relances.'],
        'presents' => ['Participants présents', 'Les entrées enregistrées à l\'accueil, avec l\'heure et l\'agent.'],
        'statistiques' => ['Statistiques finales', 'Le bilan chiffré de l\'événement.'],
    ];

    private const STATUTS = [
        'creee' => 'Créée', 'envoyee' => 'Envoyée', 'distribuee' => 'Distribuée', 'ouverte' => 'Ouverte',
        'confirmee' => 'Confirmée', 'refusee' => 'Déclinée', 'sans_reponse' => 'Sans réponse', 'arrivee' => 'Arrivé',
    ];

    public function index(Request $request, Event $event)
    {
        $this->ensureCanExport($request, $event);

        return view('espace.exports.index', [
            'event' => $event,
            'rapports' => self::RAPPORTS,
        ]);
    }

    public function download(Request $request, Event $event, string $rapport, string $format)
    {
        $this->ensureCanExport($request, $event);
        abort_unless(isset(self::RAPPORTS[$rapport]), 404);

        [$entetes, $lignes] = $this->donnees($event, $rapport);
        $lignes = array_map(fn ($ligne) => array_map(fn ($v) => $this->sur($v), $ligne), $lignes);

        $nom = Str::slug($event->name).'-'.$rapport;
        $titre = self::RAPPORTS[$rapport][0];

        if ($format === 'pdf') {
            return Pdf::loadView('exports.pdf', [
                'event' => $event,
                'titre' => $titre,
                'entetes' => $entetes,
                'lignes' => $lignes,
                'date' => EventOptions::date($event),
                'genere' => now()->timezone(EventOptions::tz($event))->format('d/m/Y H:i'),
            ])->setPaper('a4', count($entetes) > 6 ? 'landscape' : 'portrait')
              ->download($nom.'.pdf');
        }

        $export = new class($entetes, $lignes) implements FromArray, WithHeadings, ShouldAutoSize, WithCustomCsvSettings {
            public function __construct(private array $entetes, private array $lignes)
            {
            }

            public function headings(): array
            {
                return $this->entetes;
            }

            public function array(): array
            {
                return $this->lignes;
            }

            // CSV lisible par Excel en français : séparateur « ; » et accents (BOM UTF-8)
            public function getCsvSettings(): array
            {
                return ['delimiter' => ';', 'use_bom' => true];
            }
        };

        return $format === 'csv'
            ? Excel::download($export, $nom.'.csv', ExcelType::CSV)
            : Excel::download($export, $nom.'.xlsx');
    }

    // ------------------------------------------------------------------

    /** @return array{0: array, 1: array} en-têtes et lignes du rapport */
    private function donnees(Event $event, string $rapport): array
    {
        $tz = EventOptions::tz($event);
        $date = fn ($d) => $d ? $d->copy()->timezone($tz)->format('d/m/Y H:i') : '';
        $reponse = fn ($r) => match ($r?->response) {
            'accepte' => 'Confirmée', 'refuse' => 'Déclinée', default => 'Sans réponse',
        };

        if ($rapport === 'presents') {
            $rows = CheckIn::where('event_id', $event->id)
                ->whereNull('cancelled_at')
                ->with(['invitation.guest.group', 'agent'])
                ->orderBy('checked_in_at')
                ->get()
                ->map(fn ($c) => [
                    $c->invitation?->guest?->display_name ?? 'Invité supprimé',
                    $c->invitation?->guest?->group?->name,
                    $c->persons_entered,
                    $date($c->checked_in_at),
                    $c->agent?->name,
                ])->all();

            return [['Invité', 'Cercle', 'Personnes entrées', 'Heure d\'entrée', 'Agent'], $rows];
        }

        if ($rapport === 'statistiques') {
            return [['Indicateur', 'Valeur'], $this->statistiques($event)];
        }

        $entrees = CheckIn::where('event_id', $event->id)->whereNull('cancelled_at')
            ->selectRaw('invitation_id, sum(persons_entered) as n')
            ->groupBy('invitation_id')->pluck('n', 'invitation_id');

        $guests = $event->guests()->with(['group:id,name', 'invitation.rsvp'])->orderBy('last_name')->get();

        if ($rapport === 'invites') {
            $rows = $guests->map(fn ($g) => [
                $g->first_name, $g->last_name, $g->phone, $g->whatsapp, $g->email,
                $g->organization, $g->job_title, $g->group?->name, $g->allowed_seats,
                self::STATUTS[$g->invitation?->status] ?? 'Pas d\'invitation',
                $reponse($g->invitation?->rsvp),
                $g->invitation?->rsvp?->response === 'accepte' ? $g->invitation->rsvp->party_size : 0,
                (int) ($entrees[$g->invitation?->id] ?? 0),
                $g->invitation?->code,
            ])->all();

            return [['Prénom', 'Nom', 'Téléphone', 'WhatsApp', 'Email', 'Organisation', 'Fonction', 'Cercle',
                'Places', 'Statut de l\'invitation', 'Réponse', 'Personnes confirmées', 'Personnes entrées', 'Code'], $rows];
        }

        if ($rapport === 'confirmations') {
            $rows = $guests->filter(fn ($g) => $g->invitation?->rsvp?->response === 'accepte')
                ->map(fn ($g) => [
                    $g->display_name, $g->phone, $g->email, $g->group?->name, $g->allowed_seats,
                    $g->invitation->rsvp->party_size, $g->invitation->rsvp->companion_name,
                    $g->invitation->rsvp->dietary_preferences, $g->invitation->rsvp->special_needs,
                    $g->invitation->rsvp->comment, $date($g->invitation->rsvp->responded_at),
                ])->values()->all();

            return [['Invité', 'Téléphone', 'Email', 'Cercle', 'Places', 'Personnes confirmées', 'Accompagnant',
                'Préférences alimentaires', 'Besoins particuliers', 'Commentaire', 'Répondu le'], $rows];
        }

        if ($rapport === 'refus') {
            $rows = $guests->filter(fn ($g) => $g->invitation?->rsvp?->response === 'refuse')
                ->map(fn ($g) => [
                    $g->display_name, $g->phone, $g->email, $g->group?->name,
                    $g->invitation->rsvp->comment, $date($g->invitation->rsvp->responded_at),
                ])->values()->all();

            return [['Invité', 'Téléphone', 'Email', 'Cercle', 'Commentaire', 'Répondu le'], $rows];
        }

        // sans-reponse
        $rows = $guests->filter(fn ($g) => ! $g->invitation?->rsvp)
            ->map(fn ($g) => [
                $g->display_name, $g->phone, $g->email, $g->group?->name, $g->allowed_seats,
                self::STATUTS[$g->invitation?->status] ?? 'Pas d\'invitation',
                $date($g->invitation?->sent_at),
            ])->values()->all();

        return [['Invité', 'Téléphone', 'Email', 'Cercle', 'Places', 'Statut de l\'invitation', 'Envoyée le'], $rows];
    }

    private function statistiques(Event $event): array
    {
        $pct = fn ($a, $b) => $b > 0 ? round($a / $b * 100).' %' : '0 %';

        $inv = fn () => Invitation::where('event_id', $event->id)->whereHas('guest');
        $total = $inv()->count();
        $sent = $inv()->whereNotNull('sent_at')->count();
        $opened = $inv()->where('open_count', '>', 0)->count();

        $rsvps = fn () => Rsvp::whereHas('invitation', fn ($q) => $q->where('event_id', $event->id)->whereHas('guest'));
        $confirmed = $rsvps()->where('response', 'accepte')->count();
        $declined = $rsvps()->where('response', 'refuse')->count();
        $expected = (int) $rsvps()->where('response', 'accepte')->sum('party_size');

        $present = (int) CheckIn::where('event_id', $event->id)->whereNull('cancelled_at')->sum('persons_entered');

        return [
            ['Événement', $event->name],
            ['Date', EventOptions::date($event)],
            ['Invités', $event->guests()->count()],
            ['Invitations créées', $total],
            ['Invitations envoyées', $sent],
            ['Invitations ouvertes', $opened],
            ['Taux d\'ouverture', $sent > 0 ? min(100, round($opened / $sent * 100)).' %' : '0 %'],
            ['Confirmations', $confirmed],
            ['Refus', $declined],
            ['Sans réponse', max(0, $total - $confirmed - $declined)],
            ['Taux de réponse', $pct($confirmed + $declined, $total)],
            ['Participants attendus', $expected],
            ['Participants présents', $present],
            ['Taux de participation', $pct($present, $expected)],
        ];
    }

    /** Évite qu'Excel interprète une cellule comme une formule. */
    private function sur(mixed $valeur): mixed
    {
        if (! is_string($valeur) || $valeur === '') {
            return $valeur;
        }
        $premier = $valeur[0];
        if (in_array($premier, ['=', '@'], true)
            || (in_array($premier, ['+', '-'], true) && ! preg_match('/^[+-]?[\d\s().-]+$/', $valeur))) {
            return "'".$valeur;
        }

        return $valeur;
    }

    private function ensureCanExport(Request $request, Event $event): void
    {
        abort_unless($request->user()->canManageOrg($event->organization_id), 403, 'Droits insuffisants.');
    }
}