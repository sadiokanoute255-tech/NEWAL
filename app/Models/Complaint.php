<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    public const STATUS = [
        'nouveau' => 'Nouveau',
        'en_cours' => 'En cours',
        'resolu' => 'Résolu',
    ];

    protected $fillable = ['user_id', 'event_id', 'subject', 'message', 'status', 'response', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }
}