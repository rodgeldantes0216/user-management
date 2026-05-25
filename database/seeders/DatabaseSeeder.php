<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionRegistry;
use App\Support\Settings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        PermissionRegistry::syncAndRegister();
        Settings::save([
            'site_name' => 'User Management',
            'site_logo' => '',
            'mail_from_name' => 'User Management',
            'mail_from_address' => 'no-reply@user-management.local',
            'feature_registration_enabled' => true,
            'feature_audit_enabled' => true,
            'feature_notifications_enabled' => true,
        ]);

        $adminRole = Role::query()->firstOrCreate([
            'name' => User::ROLE_ADMIN,
        ], [
            'label' => 'Admin',
        ]);

        $userRole = Role::query()->firstOrCreate([
            'name' => User::ROLE_USER,
        ], [
            'label' => 'User',
        ]);

        $adminRole->permissions()->sync(Permission::query()->pluck('id'));
        $userRole->permissions()->sync(
            Permission::query()->whereIn('name', ['dashboard.view', 'notifications.view', 'notifications.update'])->pluck('id')
        );

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $admin->syncRoleByName(User::ROLE_ADMIN);

        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => User::ROLE_USER,
        ]);

        $user->syncRoleByName(User::ROLE_USER);

        // Seed the example low-code module chain for Projects, Tasks, and Task Comments.
        $this->call(ModuleBuilderDemoSeeder::class);

        // Seed a large number of test users (50,000)
        $this->call(LargeUserSeeder::class);
    }
}
