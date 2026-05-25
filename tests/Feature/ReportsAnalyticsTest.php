<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_reports_analytics(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        ActivityLog::query()->create([
            'actor_id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'action' => 'Logged in',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reports / Analytics')
            ->assertSee('User growth')
            ->assertSee('Permission usage')
            ->assertSee('Login trends')
            ->assertSee('System health');
    }

    public function test_regular_user_cannot_access_reports_analytics(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_successful_login_is_recorded_for_login_trends(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $user->id,
            'email' => $user->email,
            'action' => 'Logged in',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'logged_out_at' => null,
        ]);
        $this->assertNotNull($user->fresh()->last_seen_at);
    }

    public function test_reports_show_user_presence_distribution(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'last_seen_at' => now(),
            'logged_out_at' => null,
        ]);

        User::factory()->create([
            'last_seen_at' => now()->subMinutes(10),
            'logged_out_at' => null,
        ]);

        User::factory()->create([
            'last_seen_at' => now()->subMinutes(2),
            'logged_out_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('User presence')
            ->assertSee('Currently active')
            ->assertSee('Active, no activity')
            ->assertSee('Not active');
    }
}
