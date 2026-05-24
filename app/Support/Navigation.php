<?php

namespace App\Support;

class Navigation
{
    public static function items(): array
    {
        return config('navigation', []);
    }

    public static function permissions(): array
    {
        return collect(static::items())
            ->flatMap(function (array $item) {
                $permissions = [$item['permission']];

                return array_merge($permissions, $item['permissions'] ?? []);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
