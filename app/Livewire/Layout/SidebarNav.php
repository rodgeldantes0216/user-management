<?php

namespace App\Livewire\Layout;

use App\Models\AppNotification;
use Livewire\Component;

class SidebarNav extends Component
{
    public function render()
    {
        $counts = AppNotification::query()
            ->selectRaw('module_key, count(*) as total')
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->groupBy('module_key')
            ->pluck('total', 'module_key')
            ->all();

        $items = collect(config('navigation', []))
            ->filter(fn (array $item) => auth()->user()->hasPermissionTo($item['permission']))
            ->map(fn (array $item) => [...$item, 'badge' => $item['key'] === 'notifications'
                ? array_sum($counts)
                : ($counts[$item['key']] ?? 0),
            ])
            ->values()
            ->all();

        return view('livewire.layout.sidebar-nav', [
            'items' => $items,
        ]);
    }
}
