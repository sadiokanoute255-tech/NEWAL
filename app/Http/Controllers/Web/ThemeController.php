<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Apparence clair ou nuit, enregistrée pour chaque compte. */
class ThemeController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['theme' => 'required|in:clair,nuit']);

        $request->user()->forceFill(['theme' => $data['theme']])->save();

        return response()->json(['theme' => $data['theme']]);
    }
}