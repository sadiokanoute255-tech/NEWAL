<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rsvp extends Model
{
    protected $fillable = [
        'invitation_id', 'response', 'party_size', 'companion_name',
        'dietary_preferences', 'special_needs', 'comment', 'responded_at',
    ];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }
}
