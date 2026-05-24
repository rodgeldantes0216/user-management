<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $fillable = [
        'module_id',
        'label',
        'name',
        'type',
        'required',
        'nullable',
        'unique',
        'default_value',
        'validation_rules',
        'placeholder',
        'options',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'nullable' => 'boolean',
            'unique' => 'boolean',
            'options' => 'array',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function optionList(): array
    {
        return collect($this->options ?? [])
            ->map(fn ($option) => is_array($option) ? ($option['value'] ?? $option['label'] ?? null) : $option)
            ->filter()
            ->values()
            ->all();
    }
}
