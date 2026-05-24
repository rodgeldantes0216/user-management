<?php

namespace Tests\Feature;

use App\Livewire\Activities\Index as ActivitiesIndex;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_activity_logs(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('activities.index'))
            ->assertOk();
    }

    public function test_regular_user_cannot_access_activity_logs(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get(route('activities.index'))
            ->assertForbidden();
    }

    public function test_activity_log_delete_requires_permission(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $viewerRole = Role::query()->create([
            'name' => 'auditor',
            'label' => 'Auditor',
        ]);

        $viewerRole->permissions()->sync(
            Permission::query()->whereIn('name', ['dashboard.view', 'activities.view'])->pluck('id')
        );

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);
        $user->syncRoleByName('auditor');

        $log = ActivityLog::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'action' => 'Created user',
        ]);

        Livewire::actingAs($admin)->test(ActivitiesIndex::class)
            ->call('confirmDelete', $log->id)
            ->call('delete');

        $this->assertDatabaseMissing('activity_logs', [
            'id' => $log->id,
        ]);

        $otherLog = ActivityLog::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'action' => 'Updated role',
        ]);

        Livewire::actingAs($user)->test(ActivitiesIndex::class)
            ->call('confirmDelete', $otherLog->id)
            ->assertForbidden();
    }
}
