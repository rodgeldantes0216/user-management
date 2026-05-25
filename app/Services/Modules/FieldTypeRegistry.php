<?php

namespace App\Services\Modules;

use App\Models\ModuleField;
use Illuminate\Database\Schema\Blueprint;

class FieldTypeRegistry
{
    public const SEARCHABLE_TYPES = ['text', 'textarea', 'email', 'select', 'radio', 'rich_text', 'color'];

    public const FILTERABLE_TYPES = ['select', 'radio', 'checkbox', 'toggle', 'relationship', 'color'];

    public const DISPLAY_TEXT_TYPES = ['text', 'textarea', 'email', 'select', 'radio', 'number', 'currency', 'color'];

    public function types(): array
    {
        return ModuleField::TYPES;
    }

    public function addColumn(Blueprint $table, ModuleField $field): mixed
    {
        return match ($field->type) {
            'textarea', 'rich_text' => $table->text($field->name),
            'number' => $table->decimal($field->name, 12, 2),
            'currency' => $table->decimal($field->name, 15, 2),
            'email', 'password', 'select', 'radio', 'file', 'image', 'color' => $table->string($field->name),
            'checkbox', 'toggle' => $table->boolean($field->name)->default((bool) $field->default_value),
            'date' => $table->date($field->name),
            'datetime' => $table->dateTime($field->name),
            'json', 'date_range' => $table->json($field->name),
            'relationship' => $table->unsignedBigInteger($field->name),
            default => $table->string($field->name),
        };
    }

    public function migrationMethod(ModuleField $field): string
    {
        return match ($field->type) {
            'textarea', 'rich_text' => "text('{$field->name}')",
            'number' => "decimal('{$field->name}', 12, 2)",
            'currency' => "decimal('{$field->name}', 15, 2)",
            'checkbox', 'toggle' => "boolean('{$field->name}')",
            'date' => "date('{$field->name}')",
            'datetime' => "dateTime('{$field->name}')",
            'json', 'date_range' => "json('{$field->name}')",
            'relationship' => "unsignedBigInteger('{$field->name}')",
            default => "string('{$field->name}')",
        };
    }

    public function canHaveDefault(ModuleField $field): bool
    {
        return ! in_array($field->type, ['file', 'image', 'password', 'json', 'date_range'], true);
    }
}
