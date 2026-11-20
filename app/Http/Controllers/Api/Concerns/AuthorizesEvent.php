<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Event;
use Illuminate\Http\Request;

trait AuthorizesEvent
{
    /** Lecture : tout membre de l'organisation de l'evenement. */
    protected function ensureCanRead(Request $request, Event $event): void
    {
        $role = $request->user()->roleIn($event->organization_id);

        abort_if($role === null, 403, "Acces refuse a cet evenement.");
    }

    /** Ecriture : proprietaire ou gestionnaire. */
    protected function ensureCanWrite(Request $request, Event $event): void
    {
        abort_unless(
            $request->user()->canManageOrg($event->organization_id),
            403,
            "Vous n'avez pas le droit de modifier cet evenement."
        );
    }

    /** Envoi des invitations : proprietaire, gestionnaire ou communication. */
    protected function ensureCanSend(Request $request, Event $event): void
    {
        $role = $request->user()->roleIn($event->organization_id);

        abort_unless(
            in_array($role, ['proprietaire', 'gestionnaire', 'communication'], true),
            403,
            "Vous n'avez pas le droit d'envoyer des invitations."
        );
    }
}