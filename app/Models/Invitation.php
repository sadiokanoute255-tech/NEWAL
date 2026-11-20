<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = [
        'event_id', 'guest_id', 'code', 'token', 'qr_token', 'status',
        'last_channel', 'sent_at', 'first_opened_at', 'open_count', 'last_reminder_at',
    ];

    // Le token du QR n'est jamais exposé dans les listes (§20)
    protected $hidden = ['qr_token'];

    protected $appends = ['public_url'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'first_opened_at' => 'datetime',
            'last_reminder_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function rsvp()
    {
        return $this->hasOne(Rsvp::class);
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return config('newal.frontend_url').'/i/'.$this->token;
    }

    /** Crée l'invitation d'un invité (idempotent : une seule par invité). */
    public static function createFor(Guest $guest): self
    {
        return static::firstOrCreate(
            ['guest_id' => $guest->id],
            [
                'event_id' => $guest->event_id,
                'code' => static::newCode(),
                'token' => Str::random(48),
                'qr_token' => Str::random(48),
                'status' => 'creee',
            ]
        );
    }

    /** Code court de 8 caractères, sans caractères ambigus (0/O, 1/I). */
    private static function newCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
