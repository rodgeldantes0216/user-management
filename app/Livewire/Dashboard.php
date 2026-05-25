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
        $roleCounts = User::query()
            ->selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        $weeklyUsers = User::query()
            ->selectRaw('DATE(created_at) as day, count(*) as count')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('count', 'day')
            ->toArray();

        $labels = collect(range(6, 0))->map(function ($daysBack) {
            return now()->subDays($daysBack)->format('M j');
        })->all();

        $weeklyCounts = collect(range(6, 0))->map(function ($daysBack) use ($weeklyUsers) {
            $key = now()->subDays($daysBack)->format('Y-m-d');

            return $weeklyUsers[$key] ?? 0;
        })->all();

        return view('livewire.dashboard', [
            'totalUsers' => User::count(),
            'adminCount' => User::where('role', User::ROLE_ADMIN)->count(),
            'roleCounts' => $roleCounts,
            'signupLabels' => $labels,
            'signupCounts' => $weeklyCounts,
            'latestUsers' => User::latest()->take(5)->get(),
        ]);
    }
}
