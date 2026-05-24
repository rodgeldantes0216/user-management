<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            default => $setting->value,
        };
    }

    public static function put(string $key, mixed $value, string $type = 'string'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_bool($value) ? (string) (int) $value : (string) $value,
                'type' => $type,
            ],
        );
    }
}
