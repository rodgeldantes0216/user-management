<?php

namespace App\Livewire\Layout;

use App\Models\AppNotification;
use App\Models\Module;
use Illuminate\Support\Facades\Schema;
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
            ]);

        if (Schema::hasTable('modules')) {
            $generatedItems = Module::query()
                ->whereNotNull('generated_at')
                ->orderBy('name')
                ->get()
                ->filter(fn (Module $module) => auth()->user()->hasPermissionTo($module->permissionName('view')))
                ->map(fn (Module $module) => [
                    'key' => $module->table_name,
                    'label' => $module->name,
                    'route' => 'modules.records',
                    'route_params' => [$module->table_name],
                    'permission' => $module->permissionName('view'),
                    'badge' => $counts[$module->table_name] ?? 0,
                ]);

            $items = $items->merge($generatedItems);
        }

        $items = $items
            ->values()
            ->all();

        return view('livewire.layout.sidebar-nav', [
            'items' => $items,
        ]);
    }
}
