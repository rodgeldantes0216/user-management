<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ModuleNameGuard
{
    protected const RESERVED = [
        'add',
        'all',
        'alter',
        'and',
        'as',
        'between',
        'by',
        'create',
        'delete',
        'drop',
        'from',
        'group',
        'id',
        'index',
        'insert',
        'into',
        'join',
        'key',
        'limit',
        'not',
        'null',
        'or',
        'order',
        'select',
        'table',
        'timestamp',
        'unique',
        'update',
        'user',
        'where',
    ];

    public static function tableName(string $name): string
    {
        $table = Str::of($name)->plural()->snake()->lower()->replaceMatches('/[^a-z0-9_]/', '')->toString();

        static::assertIdentifier($table, 'table_name');

        return $table;
    }

    public static function fieldName(string $name): string
    {
        $field = Str::of($name)->snake()->lower()->replaceMatches('/[^a-z0-9_]/', '')->toString();

        static::assertIdentifier($field, 'field name');

        return $field;
    }

    public static function assertIdentifier(string $identifier, string $label): void
    {
        if (! preg_match('/^[a-z][a-z0-9_]{1,62}$/', $identifier)) {
            throw ValidationException::withMessages([
                $label => "The {$label} must start with a letter and contain only lowercase letters, numbers, and underscores.",
            ]);
        }

        if (in_array($identifier, static::RESERVED, true)) {
            throw ValidationException::withMessages([
                $label => "The {$label} uses a reserved keyword.",
            ]);
        }
    }
}
