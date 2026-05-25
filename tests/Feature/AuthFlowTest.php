<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Jane Doe')
            ->set('username', 'jane_doe')
            ->set('email', 'jane@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'jane_doe',
            'email' => 'jane@example.com',
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'membername',
            'email' => 'membername@example.com',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'membername')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
