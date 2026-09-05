<?php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'local_id',
        'activo',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function rol(): string
    {
        return $this->rol ?: 'sin_acceso';
    }

    public function esRol(string ...$roles): bool
    {
        return in_array($this->rol(), $roles, true);
    }

    public function esAdmin(): bool
    {
        return $this->rol() === 'admin';
    }

    public function esVendedor(): bool
    {
        return $this->rol() === 'vendedor';
    }

    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // bloquea usuarios desactivados
        return $this->activo ?? true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar) : null;
    }
}
