<?php

namespace Tests\Feature;

use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_can_access_notification_center(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk();
    }

    public function test_user_module_actions_create_notifications(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin)->test(UsersIndex::class)
            ->call('create')
            ->set('name', 'Notify User')
            ->set('username', 'notify_user')
            ->set('email', 'notify@example.com')
            ->set('role', User::ROLE_ADMIN)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $admin->id,
            'module_key' => 'users',
            'title' => 'User created',
        ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $notification = AppNotification::query()->create([
            'user_id' => $user->id,
            'module_key' => 'notifications',
            'title' => 'Welcome',
            'message' => 'You have a new notification.',
        ]);

        Livewire::actingAs($user)->test(NotificationsIndex::class)
            ->call('markAsRead', $notification->id);

        $this->assertDatabaseMissing('app_notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }
}
