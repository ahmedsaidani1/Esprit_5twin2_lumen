<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLES = ['admin', 'membre'];

    /**
     * Le rôle n'est jamais rempli depuis l'inscription : seuls les contrôleurs
     * réservés aux administrateurs le définissent explicitement.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'communaute_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function communaute()
    {
        return $this->belongsTo(Communaute::class);
    }

    public function installations()
    {
        return $this->hasMany(Installation::class);
    }

    public function echangesVendus()
    {
        return $this->hasMany(Echange::class, 'vendeur_id');
    }

    public function echangesAchetes()
    {
        return $this->hasMany(Echange::class, 'acheteur_id');
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['role'] ?? null, fn ($q, $v) => $q->where('role', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")
            ));
    }
}
