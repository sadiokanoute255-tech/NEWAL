<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Rules\MotDePasseFort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /** Types d'organisation (§4.2 : institution, ONG, association). */
    public const SOUS_TYPES = [
        'institution' => 'Institution publique',
        'ong' => 'ONG',
        'association' => 'Association',
        'autre' => 'Autre organisation',
    ];

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email ou mot de passe incorrect.'])
                ->onlyInput('email');
        }

        if (Auth::user()->is_suspended) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Ce compte est suspendu. Contactez l\'équipe ATAABA.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('tableau-de-bord'));
    }

    public function showRegister()
    {
        return view('auth.inscription');
    }

    public function register(Request $request): RedirectResponse
    {
        $type = (string) $request->input('account_type');
        $pro = in_array($type, ['entreprise', 'organisation', 'agence'], true);

        $data = $request->validate([
            'account_type' => 'required|in:particulier,entreprise,organisation,agence',
            'first_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => [Rule::requiredIf($pro), 'nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'confirmed', new MotDePasseFort],
            'organization_name' => [Rule::requiredIf($pro), 'nullable', 'string', 'max:255'],
            'job_title' => 'nullable|string|max:100',
            'subtype' => [Rule::requiredIf($type === 'organisation'), 'nullable', Rule::in(array_keys(self::SOUS_TYPES))],
            'sector' => 'nullable|string|max:100',
            'legal_id' => 'nullable|string|max:60',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'required' => 'Le champ « :attribute » est obligatoire.',
            'email' => 'Saisissez une adresse e-mail valide.',
            'unique' => 'La valeur saisie pour « :attribute » est déjà utilisée.',
            'confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'max' => 'Le champ « :attribute » est trop long.',
            'url' => 'Saisissez une adresse web valide, par exemple https://exemple.com.',
            'image' => 'Le logo doit être une image.',
            'mimes' => 'Le logo doit être au format JPG, PNG ou WebP.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
            'in' => 'La valeur choisie pour « :attribute » n\'est pas valide.',
        ], [
            'account_type' => 'Type de compte',
            'first_name' => 'Prénom',
            'name' => 'Nom',
            'email' => 'Adresse e-mail',
            'phone' => 'Téléphone',
            'password' => 'Mot de passe',
            'organization_name' => 'Nom',
            'job_title' => 'Fonction',
            'subtype' => 'Type d\'organisation',
            'sector' => 'Secteur d\'activité',
            'legal_id' => 'Numéro d\'identification',
            'website' => 'Site web',
            'logo' => 'Logo',
        ]);

        $user = DB::transaction(function () use ($data, $type, $pro, $request) {
            $user = User::create([
                'name' => $data['name'],
                'first_name' => $data['first_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'account_type' => $type,
                'locale' => 'fr',
            ]);

            if ($pro && ! empty($data['job_title'])) {
                $user->forceFill(['job_title' => $data['job_title']])->save();
            }

            $logo = ($pro && $request->hasFile('logo'))
                ? $request->file('logo')->store('logos', 'public')
                : null;

            // Seuls les champs qui concernent le type choisi sont conservés
            $org = new Organization();
            $org->forceFill([
                'name' => $pro ? $data['organization_name'] : trim($data['first_name'].' '.$data['name']),
                'type' => $type,
                'owner_id' => $user->id,
                'logo_path' => $logo,
                'subtype' => $type === 'organisation' ? ($data['subtype'] ?? null) : null,
                'sector' => $type === 'entreprise' ? ($data['sector'] ?? null) : null,
                'legal_id' => $pro ? ($data['legal_id'] ?? null) : null,
                'website' => $pro ? ($data['website'] ?? null) : null,
            ])->save();

            $user->organizations()->attach($org->id, ['role' => 'proprietaire']);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('tableau-de-bord');
    }

    public function logout(Request $request): RedirectResponse
    {
        $etaitAdmin = (bool) Auth::user()?->is_admin;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($etaitAdmin ? 'admin.login' : 'login');
    }
}