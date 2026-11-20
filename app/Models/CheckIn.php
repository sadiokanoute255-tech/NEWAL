<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    protected $table = 'check_ins';

    protected $fillable = [
        'invitation_id', 'event_id', 'agent_id', 'persons_entered',
        'checked_in_at', 'cancelled_at', 'cancelled_by', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'persons_entered' => 'integer',
        ];
    }

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
