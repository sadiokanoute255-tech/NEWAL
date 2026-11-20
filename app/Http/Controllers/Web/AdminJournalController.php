<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

/** Historique des actions (§39), consultable par l'administration. */
class AdminJournalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->is_admin, 403, 'Réservé aux administrateurs.');

        $action = array_key_exists((string) $request->action, ActivityLog::ACTIONS) ? $request->action : null;

        $logs = ActivityLog::with(['user:id,name,first_name', 'event:id,name'])
            ->when($action, fn ($q, $a) => $q->where('action', $a))
            ->when($request->search, fn ($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('espace.admin.historique', ['logs' => $logs, 'action' => $action]);
    }
}