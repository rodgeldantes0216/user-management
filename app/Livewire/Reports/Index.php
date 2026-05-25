<?php

namespace App\Livewire\Reports;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Reports / Analytics')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('reports.view'), 403);
    }

    public function render()
    {
        $settings = Settings::all();
        $presenceCounts = $this->presenceCounts();
        $growth = $this->dailyCounts(User::query(), 29);
        $loginTrend = $this->dailyCounts(
            ActivityLog::query()->where('action', 'Logged in'),
            13,
        );

        $permissionUsage = Permission::query()
            ->leftJoin('permission_role', 'permissions.id', '=', 'permission_role.permission_id')
            ->select('permissions.name', 'permissions.label', 'permissions.group', DB::raw('count(permission_role.role_id) as role_count'))
            ->groupBy('permissions.id', 'permissions.name', 'permissions.label', 'permissions.group')
            ->orderByDesc('role_count')
            ->orderBy('permissions.group')
            ->limit(8)
            ->get();

        $permissionGroups = Permission::query()
            ->select('group', DB::raw('count(*) as total'))
            ->groupBy('group')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'group')
            ->all();

        $recentActivity = ActivityLog::query()
            ->latest()
            ->take(6)
            ->get();

        return view('livewire.reports.index', [
            'totalUsers' => User::query()->count(),
            'newUsersToday' => User::query()->whereDate('created_at', today())->count(),
            'activeRoles' => Role::query()->count(),
            'permissionCount' => Permission::query()->count(),
            'auditEventsToday' => ActivityLog::query()->whereDate('created_at', today())->count(),
            'latestLogin' => ActivityLog::query()->where('action', 'Logged in')->latest()->first(),
            'auditEnabled' => (bool) $settings['feature_audit_enabled'],
            'notificationsEnabled' => (bool) $settings['feature_notifications_enabled'],
            'presenceLabels' => ['Currently active', 'Active, no activity', 'Not active'],
            'presenceValues' => [
                $presenceCounts['online'],
                $presenceCounts['idle'],
                $presenceCounts['offline'],
            ],
            'growthLabels' => $growth['labels'],
            'growthValues' => $growth['values'],
            'loginLabels' => $loginTrend['labels'],
            'loginValues' => $loginTrend['values'],
            'permissionLabels' => array_keys($permissionGroups),
            'permissionValues' => array_values($permissionGroups),
            'permissionUsage' => $permissionUsage,
            'recentActivity' => $recentActivity,
        ]);
    }

    protected function presenceCounts(): array
    {
        $seen = User::query()
            ->whereNotNull('last_seen_at')
            ->where(function ($query) {
                $query
                    ->whereNull('logged_out_at')
                    ->orWhereColumn('logged_out_at', '<', 'last_seen_at');
            });

        $online = (clone $seen)
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->count();

        $idle = (clone $seen)
            ->where('last_seen_at', '<', now()->subMinutes(5))
            ->where('last_seen_at', '>=', now()->subMinutes(30))
            ->count();

        $total = User::query()->count();

        return [
            'online' => $online,
            'idle' => $idle,
            'offline' => max(0, $total - $online - $idle),
        ];
    }

    protected function dailyCounts($query, int $daysBack): array
    {
        $counts = (clone $query)
            ->selectRaw('DATE(created_at) as day, count(*) as count')
            ->where('created_at', '>=', now()->subDays($daysBack)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('count', 'day')
            ->all();

        $range = collect(range($daysBack, 0));

        return [
            'labels' => $range->map(fn (int $day) => now()->subDays($day)->format('M j'))->all(),
            'values' => $range->map(fn (int $day) => $counts[now()->subDays($day)->format('Y-m-d')] ?? 0)->all(),
        ];
    }
}
