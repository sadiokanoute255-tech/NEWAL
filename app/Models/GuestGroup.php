<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestGroup extends Model
{
    protected $fillable = ['event_id', 'name', 'color'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }
}
