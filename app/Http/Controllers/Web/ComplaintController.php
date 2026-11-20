<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Réclamations de l'organisateur (§38). */
class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orgIds = $user->organizations()->pluck('organizations.id');

        return view('espace.reclamations.index', [
            'complaints' => Complaint::where('user_id', $user->id)->with('event:id,name')->latest()->get(),
            'events' => Event::whereIn('organization_id', $orgIds)
                ->where('status', '!=', 'archive')
                ->orderByDesc('starts_at')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $orgIds = $user->organizations()->pluck('organizations.id')->all();

        $data = $request->validate([
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:3000',
            'event_id' => ['nullable', Rule::exists('events', 'id')->whereIn('organization_id', $orgIds)],
        ]);

        Complaint::create($data + ['user_id' => $user->id, 'status' => 'nouveau']);

        return back()->with('succes', 'Votre réclamation a été envoyée. L\'équipe ATAABA vous répondra ici.');
    }
}