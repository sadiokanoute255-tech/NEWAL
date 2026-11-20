<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventProgramItem extends Model
{
    protected $fillable = ['event_id', 'time', 'title', 'description', 'sort_order'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}