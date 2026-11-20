<?php

namespace App\Http\Controllers\Api;

use App\Exports\GuestsTemplateExport;
use App\Http\Controllers\Api\Concerns\AuthorizesEvent;
use App\Http\Controllers\Controller;
use App\Imports\GuestsImport;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class GuestImportController extends Controller
{
    use AuthorizesEvent;

    /** Modèle Excel standard téléchargeable (§8.2). */
    public function template(Request $request, Event $event)
    {
        $this->ensureCanRead($request, $event);

        return Excel::download(new GuestsTemplateExport, 'modele_invites_newal.xlsx');
    }

    public function import(Request $request, Event $event)
    {
        $this->ensureCanWrite($request, $event);
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120']);

        $rows = Excel::toCollection(new GuestsImport, $request->file('file'))->first() ?? collect();

        $limit = $event->guestLimit();
        $count = $event->guests()->count();
        $knownPhones = $event->guests()->whereNotNull('phone')->pluck('phone')->flip()->all();
        $groups = $event->groups()->get()->keyBy(fn ($g) => mb_strtolower($g->name));

        $imported = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2; // ligne 1 = en-têtes

            $data = [
                'first_name' => $this->str($row->get('prenom')),
                'last_name' => $this->str($row->get('nom')),
                'phone' => $this->str($row->get('telephone')),
                'whatsapp' => $this->str($row->get('whatsapp')),
                'email' => $this->str($row->get('email')),
                'organization' => $this->str($row->get('organisation')),
                'job_title' => $this->str($row->get('fonction')),
                'allowed_seats' => (int) ($this->str($row->get('places')) ?? 1) ?: 1,
                'comment' => $this->str($row->get('commentaire')),
            ];
            $groupName = $this->str($row->get('cercle'));

            // Ligne entièrement vide : on ignore
            if (! array_filter($data, fn ($v) => $v !== null && $v !== 1) && ! $groupName) {
                continue;
            }

            $v = Validator::make($data, [
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email|max:150',
                'allowed_seats' => 'integer|min:1|max:100',
            ]);
            if ($v->fails()) {
                $errors[] = ['line' => $line, 'message' => $v->errors()->first()];
                continue;
            }

            if ($data['phone'] && isset($knownPhones[$data['phone']])) {
                $errors[] = ['line' => $line, 'message' => 'Doublon : ce téléphone existe déjà pour cet événement.'];
                continue;
            }

            if ($count >= $limit) {
                $errors[] = ['line' => $line, 'message' => "Quota d'invités atteint ({$limit}). Import arrêté."];
                break;
            }

            $group = null;
            if ($groupName) {
                $key = mb_strtolower($groupName);
                $group = $groups->get($key);
                if (! $group) {
                    $group = $event->groups()->create(['name' => $groupName]);
                    $groups->put($key, $group);
                }
            }

            $event->guests()->create($data + ['guest_group_id' => $group?->id]);

            if ($data['phone']) {
                $knownPhones[$data['phone']] = true;
            }
            $count++;
            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => count($errors),
            'errors' => $errors,
        ];
    }

    private function str($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
