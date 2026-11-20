<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public const ACTIONS = [
        'creation' => 'Création',
        'modification' => 'Modification',
        'suppression' => 'Suppression',
        'envoi' => 'Envoi',
        'scan' => 'Scan',
        'statut' => 'Changement de statut',
        'paiement' => 'Paiement',
    ];

    protected $fillable = ['user_id', 'event_id', 'action', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }
}