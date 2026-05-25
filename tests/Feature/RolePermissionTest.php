<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_permissions_are_synced_automatically(): void
    {
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertDatabaseHas('permissions', ['name' => 'dashboard.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'users.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'roles.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'system-health.view']);
    }

    public function test_user_without_roles_permission_cannot_access_roles_module(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_roles_module(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk();
    }
}
