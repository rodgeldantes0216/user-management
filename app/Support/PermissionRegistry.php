<?php

namespace App\Support;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PermissionRegistry
{
    public static function syncAndRegister(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach (Navigation::permissions() as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName],
                [
                    'label' => Str::of($permissionName)->replace('.', ' ')->title()->toString(),
                    'group' => Str::before($permissionName, '.'),
                ],
            );
        }

        if (Schema::hasTable('modules')) {
            Module::query()->each(function (Module $module) {
                foreach (['view', 'create', 'update', 'delete'] as $ability) {
                    $permissionName = $module->permissionName($ability);

                    Permission::firstOrCreate(
                        ['name' => $permissionName],
                        [
                            'label' => Str::of($permissionName)->replace('.', ' ')->title()->toString(),
                            'group' => $module->table_name,
                        ],
                    );
                }
            });
        }

        Permission::query()->pluck('name')->each(function (string $permissionName) {
            Gate::define($permissionName, fn ($user) => $user->hasPermissionTo($permissionName));
        });
    }
}
