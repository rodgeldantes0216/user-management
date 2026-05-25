<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_system_health_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('system-health.index'))
            ->assertOk()
            ->assertSee('System Health')
            ->assertSee('Database connectivity')
            ->assertSee('Cache round-trip')
            ->assertSee('Queue status')
            ->assertSee('Failed jobs')
            ->assertSee('Mail configuration')
            ->assertSee('Application key');
    }

    public function test_regular_user_cannot_access_system_health_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get(route('system-health.index'))
            ->assertForbidden();
    }
}
