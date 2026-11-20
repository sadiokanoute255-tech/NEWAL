<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** Connexion réservée aux administrateurs ATAABA (§4.1). */
class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (auth()->check() && auth()->user()->is_admin) {
            return redirect()->route('admin.tableau');
        }

        return view('auth.admin-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Message volontairement vague : on ne révèle pas si le compte existe ou s'il est administrateur
        if (! $user || ! $user->is_admin || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Identifiants incorrects ou accès non autorisé.'])
                ->onlyInput('email');
        }

        if ($user->is_suspended) {
            return back()
                ->withErrors(['email' => 'Ce compte est suspendu.'])
                ->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.tableau'));
    }
}