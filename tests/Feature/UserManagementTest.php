<?php

namespace Tests\Feature;

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
            ->set('email', 'new@example.com')
            ->set('role', User::ROLE_USER)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'role' => User::ROLE_USER,
        ]);

        $component
            ->call('edit', $target->id)
            ->set('name', 'Updated User')
            ->set('role', User::ROLE_ADMIN)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Updated User',
            'role' => User::ROLE_ADMIN,
        ]);

        $newUser = User::where('email', 'new@example.com')->firstOrFail();

        $component
            ->call('confirmDelete', $newUser->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', [
            'id' => $newUser->id,
        ]);
    }
}
