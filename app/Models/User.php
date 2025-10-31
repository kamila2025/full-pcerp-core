<?php

namespace App\Models;

use App\Enums\PermissionNameEnum;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, \Filament\Models\Contracts\FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    use \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_has_user');
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($this->can(PermissionNameEnum::所有權限)) return true;

        return $this->can(PermissionNameEnum::後台管理) && $this->hasVerifiedEmail();
    }
}
