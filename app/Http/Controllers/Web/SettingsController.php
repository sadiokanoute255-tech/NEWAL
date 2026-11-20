<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Paramètres de l'organisation : identité, selon le type de compte (§36). */
class SettingsController extends Controller
{
    public function show(Request $request)
    {
        return view('espace.parametres', ['org' => $this->organisation($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $org = $this->organisation($request);
        $pro = in_array($org->type, ['entreprise', 'organisation', 'agence'], true);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
            'website' => 'nullable|url|max:255',
            'sector' => 'nullable|string|max:100',
            'legal_id' => 'nullable|string|max:60',
            'subtype' => [Rule::requiredIf($org->type === 'organisation'), 'nullable', Rule::in(array_keys(AuthController::SOUS_TYPES))],
        ]);

        $changes = ['name' => $data['name']];

        if ($pro) {
            $changes['legal_id'] = $data['legal_id'] ?? null;
            $changes['website'] = $data['website'] ?? null;
            if ($org->type === 'entreprise') {
                $changes['sector'] = $data['sector'] ?? null;
            }
            if ($org->type === 'organisation') {
                $changes['subtype'] = $data['subtype'] ?? null;
            }
        }

        if ($request->hasFile('logo')) {
            if ($org->logo_path) {
                Storage::disk('public')->delete($org->logo_path);
            }
            $changes['logo_path'] = $request->file('logo')->store('logos', 'public');
        } elseif ($request->boolean('remove_logo') && $org->logo_path) {
            Storage::disk('public')->delete($org->logo_path);
            $changes['logo_path'] = null;
        }

        $org->forceFill($changes)->save();

        return back()->with('succes', 'Paramètres enregistrés.');
    }

    /** L'organisation dont l'utilisateur est propriétaire. */
    private function organisation(Request $request)
    {
        $org = $request->user()->ownedOrganization();
        abort_unless($org, 403, 'Réservé au propriétaire de l\'organisation.');

        return $org;
    }
}