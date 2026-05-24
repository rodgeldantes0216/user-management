<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationCenter
{
    public static function notifyForModule(string $moduleKey, string $title, string $message, string $viewPermission, ?User $actor = null, array $meta = []): void
    {
        if (! Settings::all()['feature_notifications_enabled']) {
            return;
        }

        $recipients = User::query()
            ->with('roles.permissions')
            ->get()
            ->filter(fn (User $user) => $user->hasPermissionTo($viewPermission));

        if ($actor && ! $recipients->contains('id', $actor->id)) {
            $recipients->push($actor);
        }

        if ($recipients->isEmpty()) {
            return;
        }

        DB::table((new AppNotification())->getTable())->insert(
            $recipients
                ->map(fn (User $user) => [
                    'user_id' => $user->id,
                    'actor_id' => $actor?->id,
                    'module_key' => $moduleKey,
                    'title' => $title,
                    'message' => $message,
                    'meta' => json_encode($meta),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all()
        );
    }
}
