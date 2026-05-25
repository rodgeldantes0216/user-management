<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Activity
{
    public static function log(string $action, ?Model $subject = null, array $meta = [], ?User $actor = null): void
    {
        if (! Settings::all()['feature_audit_enabled']) {
            return;
        }

        $actor ??= Auth::user();
        $requestMeta = [];

        if (! app()->runningInConsole() && request()) {
            $requestMeta = [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];
        }

        DB::table((new ActivityLog())->getTable())->insert([
            'actor_id' => $actor?->id,
            'name' => $actor?->name ?? 'System',
            'email' => $actor?->email ?? 'system@local',
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => json_encode(array_filter([...$requestMeta, ...$meta], fn ($value) => $value !== null && $value !== '')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
