<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\PermissionRegistry;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
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

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName) || $this->role === $roleName;
    }

    public function hasPermissionTo(string $permissionName): bool
    {
        $this->loadMissing('roles.permissions');

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains('name', $permissionName);
    }

    public function syncRoleByName(string $roleName): void
    {
        PermissionRegistry::syncAndRegister();

        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['label' => str($roleName)->headline()->toString()],
        );

        if ($role->permissions()->doesntExist()) {
            $permissionIds = $roleName === self::ROLE_ADMIN
                ? Permission::query()->pluck('id')
                : Permission::query()->whereIn('name', ['dashboard.view', 'notifications.view', 'notifications.update'])->pluck('id');

            $role->permissions()->sync($permissionIds);
        }

        $this->roles()->sync([$role->id]);
        $this->forceFill(['role' => $role->name])->save();
        $this->unsetRelation('roles');
    }

    public function primaryRoleName(): ?string
    {
        $this->loadMissing('roles');

        return $this->roles->first()?->name ?? $this->role;
    }
}
