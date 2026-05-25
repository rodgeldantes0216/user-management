<?php

namespace App\Support;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionRegistry
{
    public static function syncAndRegister(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        $permissions = [];

        // Gather navigation permissions
        foreach (Navigation::permissions() as $permissionName) {
            $permissions[$permissionName] = [
                'name' => $permissionName,
                'label' => Str::of($permissionName)->replace('.', ' ')->title()->toString(),
                'group' => Str::before($permissionName, '.'),
            ];
        }

        // Gather module permissions in bulk
        if (Schema::hasTable('modules')) {
            Module::query()->get(['table_name'])->each(function (Module $module) use (&$permissions) {
                foreach (Module::PERMISSION_ABILITIES as $ability) {
                    $permissionName = $module->permissionName($ability);
                    $permissions[$permissionName] = [
                        'name' => $permissionName,
                        'label' => Str::of($permissionName)->replace('.', ' ')->title()->toString(),
                        'group' => $module->table_name,
                    ];
                }
            });
        }

        // Insert missing permissions in a single batch
        if (! empty($permissions)) {
            $existing = Permission::query()->whereIn('name', array_keys($permissions))->pluck('name')->all();
            $missing = array_diff(array_keys($permissions), $existing);

            if (! empty($missing)) {
                $rows = array_map(fn ($name) => [
                    'name' => $permissions[$name]['name'],
                    'label' => $permissions[$name]['label'],
                    'group' => $permissions[$name]['group'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $missing);

                // Use insert to batch-create missing permission rows
                Permission::query()->insert($rows);
            }
        }

        // Ensure admin role has all permissions
        if (Schema::hasTable('roles')) {
            $adminRole = Role::query()->where('name', 'admin')->first();

            if ($adminRole) {
                $adminRole->permissions()->syncWithoutDetaching(
                    Permission::query()->pluck('id')->all()
                );
            }
        }

        // Use a single Gate::before callback to check permissions efficiently
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo($ability) ? true : null;
            }

            return null;
        });
    }
}
