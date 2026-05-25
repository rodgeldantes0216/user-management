<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_users(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $target = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
        ]);

        $component = Livewire::actingAs($admin)->test(Index::class);

        $component
            ->call('create')
            ->set('name', 'New User')
            ->set('username', 'new_user')
            ->set('email', 'new@example.com')
            ->set('role', User::ROLE_USER)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'username' => 'new_user',
            'email' => 'new@example.com',
            'role' => User::ROLE_USER,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'name' => $admin->name,
            'email' => $admin->email,
            'action' => 'Created user',
        ]);

        $component
            ->call('edit', $target->id)
            ->set('name', 'Updated User')
            ->set('username', 'updated_user')
            ->set('role', User::ROLE_ADMIN)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Updated User',
            'username' => 'updated_user',
            'role' => User::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'name' => $admin->name,
            'email' => $admin->email,
            'action' => 'Updated user',
        ]);

        $newUser = User::where('email', 'new@example.com')->firstOrFail();

        $component
            ->call('confirmDelete', $newUser->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', [
            'id' => $newUser->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'name' => $admin->name,
            'email' => $admin->email,
            'action' => 'Deleted user',
        ]);
    }

    public function test_user_table_shows_presence_statuses(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->create([
            'name' => 'Online User',
            'last_seen_at' => now(),
            'logged_out_at' => null,
        ]);

        User::factory()->create([
            'name' => 'Idle User',
            'last_seen_at' => now()->subMinutes(10),
            'logged_out_at' => null,
        ]);

        User::factory()->create([
            'name' => 'Offline User',
            'last_seen_at' => now()->subMinutes(3),
            'logged_out_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Status')
            ->assertSee('Currently active')
            ->assertSee('Active, no activity')
            ->assertSee('Not active');
    }
}
