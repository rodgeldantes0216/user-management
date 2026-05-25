<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ModuleField extends Model
{
    use HasFactory;

    public const TYPES = [
        'text',
        'textarea',
        'number',
        'email',
        'password',
        'select',
        'checkbox',
        'radio',
        'toggle',
        'date',
        'datetime',
        'file',
        'image',
        'rich_text',
        'color',
        'json',
        'currency',
        'date_range',
        'relationship',
    ];

    protected $fillable = [
        'module_id',
        'label',
        'name',
        'type',
        'relationship_module_id',
        'relationship_display_field',
        'relationship_type',
        'required',
        'nullable',
        'unique',
        'default_value',
        'validation_rules',
        'validation_config',
        'placeholder',
        'options',
        'condition_field',
        'condition_operator',
        'condition_value',
        'condition_config',
        'computed_config',
        'show_in_list',
        'visible_in_form',
        'searchable',
        'filterable',
        'sortable',
        'group_name',
        'group_type',
        'column_span',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'nullable' => 'boolean',
            'unique' => 'boolean',
            'options' => 'array',
            'validation_config' => 'array',
            'condition_config' => 'array',
            'computed_config' => 'array',
            'show_in_list' => 'boolean',
            'visible_in_form' => 'boolean',
            'searchable' => 'boolean',
            'filterable' => 'boolean',
            'sortable' => 'boolean',
            'column_span' => 'integer',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function relationshipModule(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'relationship_module_id');
    }

    public function optionList(): array
    {
        return collect($this->options ?? [])
            ->map(fn ($option) => is_array($option) ? ($option['value'] ?? $option['label'] ?? null) : $option)
            ->filter()
            ->values()
            ->all();
    }

    public function hasCondition(): bool
    {
        return filled($this->condition_config)
            || (filled($this->condition_field) && filled($this->condition_operator));
    }

    public function isComputed(): bool
    {
        return filled($this->computed_config['expression'] ?? null);
    }

    public function persistsComputedValue(): bool
    {
        return (bool) ($this->computed_config['persist'] ?? true);
    }

    public function relationshipOptions(): array
    {
        if (! $this->relationshipModule || ! $this->relationship_display_field) {
            return [];
        }

        return DB::table($this->relationshipModule->table_name)
            ->select(['id', $this->relationship_display_field])
            ->orderBy($this->relationship_display_field)
            ->limit(250)
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'label' => $record->{$this->relationship_display_field} ?? ('#'.$record->id),
            ])
            ->all();
    }
}
