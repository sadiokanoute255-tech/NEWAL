<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'first_name'        => 'nullable|string|max:255',
            'email'             => 'required|email|max:255|unique:users,email',
            'phone'             => 'nullable|string|max:30|unique:users,phone',
            'password'          => 'required|string|min:8|confirmed',
            'account_type'      => 'nullable|in:particulier,entreprise,organisation,agence',
            'locale'            => 'nullable|string|max:5',
            'organization_name' => 'nullable|string|max:255',
        ]);

        $accountType = $data['account_type'] ?? 'particulier';

        $user = DB::transaction(function () use ($data, $accountType) {
            $user = User::create([
                'name'         => $data['name'],
                'first_name'   => $data['first_name'] ?? null,
                'email'        => $data['email'],
                'phone'        => $data['phone'] ?? null,
                'password'     => $data['password'], // hache par le cast "hashed"
                'account_type' => $accountType,
                'locale'       => $data['locale'] ?? 'fr',
            ]);

            $org = Organization::create([
                'name'     => $data['organization_name']
                    ?? trim(($data['first_name'] ?? '') . ' ' . $data['name']),
                'type'     => $accountType,
                'owner_id' => $user->id,
            ]);

            $user->organizations()->attach($org->id, ['role' => 'proprietaire']);

            return $user;
        });

        return response()->json([
            'user'  => $user->load('organizations'),
            'token' => $user->createToken('auth_token')->plainTextToken,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        return response()->json([
            'user'  => $user->load('organizations'),
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function me(Request $request)
    {
        return $request->user()->load('organizations');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Deconnecte.']);
    }
}