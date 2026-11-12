<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\ModuleAccess;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isAdministrator(): bool
    {
        return $this->role?->name === 'Administrator';
    }

    /**
     * The level of access this user's role has to a module.
     *
     * Administrators always have manage access everywhere; other roles read
     * the grant rows in role_module_access.
     */
    public function moduleLevel(string $module): string
    {
        if ($this->isAdministrator()) {
            return RoleModuleAccess::LEVEL_MANAGE;
        }

        return ModuleAccess::level($this->role_id, $module);
    }

    /**
     * Whether the user can access a module at least at the given level.
     */
    public function canModule(string $module, string $required = RoleModuleAccess::LEVEL_VIEW): bool
    {
        return RoleModuleAccess::rank($this->moduleLevel($module)) >= RoleModuleAccess::rank($required);
    }
}
