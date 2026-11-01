<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /** Rôles gérés par Spatie Permission (voir la migration move_roles_to_spatie_permission). */
    public const ROLES = ['admin', 'membre'];

    /**
     * Le rôle n'est pas une colonne : il est attribué avec assignRole()/syncRoles()
     * par les seuls endroits autorisés (inscription, gestion des utilisateurs par l'admin).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'communaute_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Rôle principal, pour l'affichage et l'API ("admin" ou "membre").
     * Accesseur classique : une méthode role() masquerait le scope Spatie User::role('admin').
     */
    public function getRoleAttribute(): string
    {
        return $this->getRoleNames()->first() ?? 'membre';
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? route('users.photo', ['user' => $this->id, 'v' => md5($this->photo_path)]) : null;
    }

    public function communaute()
    {
        return $this->belongsTo(Communaute::class);
    }

    public function installations()
    {
        return $this->hasMany(Installation::class);
    }

    public function compteurs()
    {
        return $this->hasMany(Compteur::class);
    }

    public function echangesVendus()
    {
        return $this->hasMany(Echange::class, 'vendeur_id');
    }

    public function echangesAchetes()
    {
        return $this->hasMany(Echange::class, 'acheteur_id');
    }

    public function aDesEchanges(): bool
    {
        return $this->echangesVendus()->exists() || $this->echangesAchetes()->exists();
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['role'] ?? null, fn ($q, $v) => $q->role($v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")
            ));
    }
}
