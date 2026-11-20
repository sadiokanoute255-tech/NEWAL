<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'event_id', 'invitation_id', 'sent_by', 'channel', 'type',
        'body', 'status', 'cost', 'scheduled_at',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }
}
