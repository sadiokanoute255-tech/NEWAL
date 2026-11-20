<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    /** Quota d'invités tant qu'aucune offre n'est choisie (offre Essentiel). */
    public const DEFAULT_GUEST_LIMIT = 50;

    public const DEFAULT_GROUPS = ['Famille', 'Amis', 'Collègues', 'VIP', 'Partenaires', 'Autres'];

    protected $fillable = [
        'organization_id', 'created_by', 'template_id', 'plan_id',
        'name', 'slug', 'type', 'description',
        'starts_at', 'ends_at', 'timezone',
        'venue_name', 'address', 'latitude', 'longitude', 'map_url', 'cover_path',
        'dress_code', 'instructions',
        'contact_name', 'contact_phone', 'contact_email', 'website', 'social_links',
        'visibility', 'status', 'rsvp_settings', 'invitation_design',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'social_links' => 'array',
            'rsvp_settings' => 'array',
            'invitation_design' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Chaque nouvel événement reçoit les cercles par défaut (§9), modifiables ensuite
        static::created(function (Event $event) {
            foreach (self::DEFAULT_GROUPS as $name) {
                $event->groups()->create(['name' => $name]);
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function programItems()
    {
        return $this->hasMany(EventProgramItem::class)->orderBy('sort_order');
    }

    public function groups()
    {
        return $this->hasMany(GuestGroup::class);
    }

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }

    public function guestLimit(): int
    {
        return $this->plan?->max_guests ?? self::DEFAULT_GUEST_LIMIT;
        }

    /**
     * Événements accessibles à un utilisateur : toute l'organisation pour le propriétaire
     * ou « tous les événements », sinon seulement ceux qui lui sont affectés.
     */
    public function scopeAccessibleBy($query, User $user)
    {
        $organisations = $user->organizations()->get();

        if ($organisations->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        $affectes = DB::table('event_access')->where('user_id', $user->id)->select('event_id');

        return $query->where(function ($q) use ($organisations, $affectes) {
            foreach ($organisations as $org) {
                if ($org->pivot->role === 'proprietaire' || $org->pivot->all_events) {
                    $q->orWhere('events.organization_id', $org->id);
                } else {
                    $q->orWhere(fn ($w) => $w
                        ->where('events.organization_id', $org->id)
                        ->whereIn('events.id', $affectes));
                }
            }
        });
    }
        }
