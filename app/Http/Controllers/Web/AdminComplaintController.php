<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Traitement des réclamations par l'administration (§38). */
class AdminComplaintController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $statut = array_key_exists((string) $request->status, Complaint::STATUS) ? $request->status : null;

        $complaints = Complaint::with(['user:id,name,first_name,email', 'event:id,name'])
            ->when($statut, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $comptes = Complaint::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('espace.admin.reclamations', [
            'complaints' => $complaints,
            'comptes' => $comptes,
            'statut' => $statut,
        ]);
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(Complaint::STATUS)),
            'response' => 'nullable|string|max:3000',
        ]);

        $complaint->update([
            'status' => $data['status'],
            'response' => $data['response'] ?: null,
            'resolved_at' => $data['status'] === 'resolu' ? ($complaint->resolved_at ?? now()) : null,
        ]);

        return back()->with('succes', 'Réclamation mise à jour.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->is_admin, 403, 'Réservé aux administrateurs.');
    }
}