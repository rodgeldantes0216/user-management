<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationCenter
{
    public static function notifyForModule(string $moduleKey, string $title, string $message, string $viewPermission, ?User $actor = null, array $meta = []): void
    {
        if (! Settings::all()['feature_notifications_enabled']) {
            return;
        }

        $recipientIds = static::recipientIds($viewPermission);

        if ($actor && ! $recipientIds->contains($actor->id)) {
            $recipientIds->push($actor->id);
        }

        if ($recipientIds->isEmpty()) {
            return;
        }

        $now = now();
        $notificationTable = (new AppNotification)->getTable();

        $recipientIds
            ->unique()
            ->values()
            ->chunk(1000)
            ->each(function (Collection $chunk) use ($notificationTable, $actor, $moduleKey, $title, $message, $meta, $now) {
                DB::table($notificationTable)->insert(
                    $chunk
                        ->map(fn (int $userId) => [
                            'user_id' => $userId,
                            'actor_id' => $actor?->id,
                            'module_key' => $moduleKey,
                            'title' => $title,
                            'message' => $message,
                            'meta' => json_encode($meta),
                            'read_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all()
                );
            });
    }

    protected static function recipientIds(string $viewPermission): Collection
    {
        return DB::table('users')
            ->select('users.id')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('permission_role', 'permission_role.role_id', '=', 'role_user.role_id')
            ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
            ->where('permissions.name', $viewPermission)
            ->distinct()
            ->pluck('users.id');
    }
}
