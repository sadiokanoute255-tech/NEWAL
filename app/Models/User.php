<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // "name" = nom de famille, "first_name" = prénom
    protected $fillable = [
        'name', 'first_name', 'email', 'phone', 'password',
        'account_type', 'locale',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_suspended' => 'boolean',
        ];
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)->withPivot('role', 'title', 'all_events')->withTimestamps();
    }

    /** Rôle de l'utilisateur dans une organisation (null = pas membre). */
    public function roleIn(int $organizationId): ?string
    {
        return $this->organizations()
            ->where('organizations.id', $organizationId)
            ->first()?->pivot->role;
    }

    /** Peut créer/modifier (propriétaire ou gestionnaire). */
    public function canManageOrg(int $organizationId): bool
    {
        return in_array($this->roleIn($organizationId), ['proprietaire', 'gestionnaire'], true);
    }

    // --- Droits des rôles (§40) ---

    /** Consulter événements, invités et invitations (tous les rôles sauf l'accueil). */
    public function canReadOrg(int $organizationId): bool
    {
        return in_array($this->roleIn($organizationId), Roles::LECTURE, true);
    }

    public function canSeeStats(int $organizationId): bool
    {
        return in_array($this->roleIn($organizationId), Roles::STATS, true);
    }

    public function canSendInvitations(int $organizationId): bool
    {
        return in_array($this->roleIn($organizationId), Roles::INVITATIONS, true);
    }

    public function canScan(int $organizationId): bool
    {
        return in_array($this->roleIn($organizationId), Roles::SCAN, true);
    }

    // --- Accès par événement ---

    /** Propriétaire, ou accès affecté à tous les événements de l'organisation. */
    public function hasFullAccess(int $organizationId): bool
    {
        $membre = $this->organizations()->where('organizations.id', $organizationId)->first();

        return $membre !== null
            && ($membre->pivot->role === 'proprietaire' || (bool) $membre->pivot->all_events);
    }

    public function canAccessEvent(Event $event): bool
    {
        if ($this->roleIn($event->organization_id) === null) {
            return false;
        }
        if ($this->hasFullAccess($event->organization_id)) {
            return true;
        }

        return DB::table('event_access')
            ->where('user_id', $this->id)
            ->where('event_id', $event->id)
            ->exists();
    }

    /** Rôle dans la première organisation de l'utilisateur (sert à choisir son menu). */
    public function mainRole(): ?string
    {
        return $this->organizations()->first()?->pivot->role;
    }

    /** L'organisation dont l'utilisateur est propriétaire. */
    public function ownedOrganization(): ?Organization
    {
        return $this->organizations()->wherePivot('role', 'proprietaire')->first();
    }
}