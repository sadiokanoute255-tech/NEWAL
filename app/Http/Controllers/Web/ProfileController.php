<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Compte de l'utilisateur (§5). */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('espace.profil', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('succes', 'Profil mis à jour.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
                        'password' => ['required', 'string', 'confirmed', new MotDePasseFort],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('succes', 'Mot de passe modifié.');
    }
}