<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id', 'guest_group_id', 'event_table_id',
        'first_name', 'last_name', 'phone', 'whatsapp', 'email',
        'organization', 'job_title', 'allowed_seats', 'comment',
    ];

    protected function casts(): array
    {
        return ['allowed_seats' => 'integer'];
    }

    protected static function booted(): void
    {
        static::created(function (Guest $guest) {
            Invitation::createFor($guest);
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function group()
    {
        return $this->belongsTo(GuestGroup::class, 'guest_group_id');
    }

    public function invitation()
    {
        return $this->hasOne(Invitation::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.$this->last_name);
    }
}
