<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', [
            'totalUsers' => User::count(),
            'adminCount' => User::where('role', User::ROLE_ADMIN)->count(),
            'latestUsers' => User::latest()->take(5)->get(),
        ]);
    }
}
