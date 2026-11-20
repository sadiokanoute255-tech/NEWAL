<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

/** Import des invités depuis Excel ou CSV (§8.2). */
class GuestImportController extends Controller
{
    private const COULEURS = ['#B8432C', '#E8A317', '#2F7D5B', '#2B5BA8', '#7A3E8E', '#5c5247'];

    public function create(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);

        return view('espace.invites.import', [
            'event' => $event,
            'total' => $event->guests()->count(),
            'limit' => $event->guestLimit(),
        ]);
    }

    /** Modèle standard téléchargeable. */
    public function template(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);

        return Excel::download(new class implements FromArray, WithHeadings {
            public function headings(): array
            {
                return ['Prénom', 'Nom', 'Téléphone', 'WhatsApp', 'Email', 'Organisation', 'Fonction', 'Cercle', 'Places', 'Commentaire'];
            }

            public function array(): array
            {
                return [
                    ['Awa', 'Ndiaye', '77 123 45 67', '', 'awa@exemple.com', '', '', 'Famille', 4, 'Exemple : à supprimer'],
                ];
            }
        }, 'modele-invites-newal.xlsx');
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->ensureCanWrite($request, $event);

        $request->validate(['fichier' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120']);
        $file = $request->file('fichier');

        // Un CSV français utilise souvent « ; » comme séparateur
        $delimiter = ',';
        if (in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            $handle = fopen($file->getRealPath(), 'r');
            $premiere = (string) fgets($handle);
            fclose($handle);
            $delimiter = substr_count($premiere, ';') > substr_count($premiere, ',') ? ';' : ',';
        }

        $import = new class($delimiter) implements WithHeadingRow, WithCustomCsvSettings {
            public function __construct(private string $delimiter)
            {
            }

            public function getCsvSettings(): array
            {
                return ['delimiter' => $this->delimiter];
            }
        };

        try {
            $rows = Excel::toArray($import, $file)[0] ?? [];
        } catch (\Throwable $e) {
            return back()->withErrors(['fichier' => 'Fichier illisible. Utilisez le modèle Excel ou un fichier CSV.']);
        }

        if ($rows === [] || ! array_key_exists('nom', $rows[0])) {
            return back()->withErrors(['fichier' => "Colonne « Nom » introuvable. Utilisez le modèle téléchargeable et gardez la première ligne d'en-têtes."]);
        }
        if (count($rows) > 2000) {
            return back()->withErrors(['fichier' => 'Maximum 2 000 lignes par import.']);
        }

        $limit = $event->guestLimit();
        $count = $event->guests()->count();
        $groups = $event->groups()->get()->keyBy(fn ($g) => mb_strtolower($g->name));
        $phones = array_flip(array_filter(
            $event->guests()->whereNotNull('phone')->pluck('phone')->map(fn ($p) => $this->digits($p))->all()
        ));

        $importes = 0;
        $ignores = [];
        $quota = false;

        foreach ($rows as $i => $row) {
            $ligne = $i + 2; // ligne 1 = en-têtes

            if (collect($row)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isEmpty()) {
                continue; // ligne vide
            }

            $nom = $this->texte($row['nom'] ?? null);
            $prenom = $this->texte($row['prenom'] ?? null);
            $label = trim($prenom.' '.$nom) ?: '(sans nom)';

            if ($count >= $limit) {
                $quota = true;
                break;
            }
            if ($nom === '') {
                $ignores[] = [$ligne, $label, 'Le nom est obligatoire.'];
                continue;
            }

            $email = $this->texte($row['email'] ?? null);
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $ignores[] = [$ligne, $label, "Adresse e-mail invalide ($email)."];
                continue;
            }

            $placesTexte = $this->texte($row['places'] ?? $row['nombre_de_places'] ?? null);
            if ($placesTexte === '') {
                $places = 1;
            } elseif (ctype_digit($placesTexte) && (int) $placesTexte >= 1 && (int) $placesTexte <= 100) {
                $places = (int) $placesTexte;
            } else {
                $ignores[] = [$ligne, $label, "Nombre de places invalide ($placesTexte) : entre 1 et 100."];
                continue;
            }

            $phone = $this->texte($row['telephone'] ?? null);
            $cle = $this->digits($phone);
            if ($cle !== '' && isset($phones[$cle])) {
                $ignores[] = [$ligne, $label, "Téléphone déjà présent dans l'événement ($phone)."];
                continue;
            }

            // Cercle : retrouvé par son nom, créé s'il n'existe pas
            $nomCercle = $this->texte($row['cercle'] ?? $row['groupe'] ?? null);
            $groupId = null;
            if ($nomCercle !== '') {
                $cleCercle = mb_strtolower($nomCercle);
                if (! $groups->has($cleCercle)) {
                    $groups[$cleCercle] = $event->groups()->create([
                        'name' => mb_substr($nomCercle, 0, 100),
                        'color' => self::COULEURS[$groups->count() % count(self::COULEURS)],
                    ]);
                }
                $groupId = $groups[$cleCercle]->id;
            }

            $event->guests()->create([
                'first_name' => $prenom !== '' ? mb_substr($prenom, 0, 255) : null,
                'last_name' => mb_substr($nom, 0, 255),
                'phone' => $phone !== '' ? mb_substr($phone, 0, 30) : null,
                'whatsapp' => ($w = $this->texte($row['whatsapp'] ?? null)) !== '' ? mb_substr($w, 0, 30) : null,
                'email' => $email !== '' ? mb_substr($email, 0, 150) : null,
                'organization' => ($o = $this->texte($row['organisation'] ?? null)) !== '' ? mb_substr($o, 0, 255) : null,
                'job_title' => ($f = $this->texte($row['fonction'] ?? null)) !== '' ? mb_substr($f, 0, 255) : null,
                'guest_group_id' => $groupId,
                'allowed_seats' => $places,
                'comment' => ($c = $this->texte($row['commentaire'] ?? null)) !== '' ? $c : null,
            ]);

            if ($cle !== '') {
                $phones[$cle] = true;
            }
            $count++;
            $importes++;
        }

        return redirect()->route('invites.import', $event)->with('rapport', [
            'importes' => $importes,
            'ignores' => array_slice($ignores, 0, 100),
            'nb_ignores' => count($ignores),
            'quota' => $quota ? $limit : null,
        ]);
    }

    // ------------------------------------------------------------------

    /** Excel renvoie parfois un numéro de téléphone sous forme de nombre. */
    private function texte(mixed $valeur): string
    {
        if ($valeur === null) {
            return '';
        }
        if (is_int($valeur) || is_float($valeur)) {
            return number_format($valeur, 0, '', '');
        }

        return trim((string) $valeur);
    }

    private function digits(?string $valeur): string
    {
        return preg_replace('/\D+/', '', (string) $valeur);
    }

    private function ensureCanWrite(Request $request, Event $event): void
    {
        abort_unless($request->user()->canManageOrg($event->organization_id), 403, 'Droits insuffisants.');
    }
}