<?php

namespace App\Services\Modules;

use App\Models\Module;
use App\Models\ModuleField;
use App\Models\ModuleSnapshot;
use App\Models\User;

class ModuleSnapshotService
{
    public function snapshot(Module $module, ?User $actor = null, ?string $label = null): ModuleSnapshot
    {
        $module->load(['fields.relationshipModule']);

        $version = ((int) $module->snapshots()->max('version')) + 1;

        return $module->snapshots()->create([
            'version' => $version,
            'label' => $label ?? 'Schema v'.$version,
            'schema' => $this->schema($module),
            'created_by' => $actor?->id,
        ]);
    }

    public function schema(Module $module): array
    {
        $module->load(['fields.relationshipModule']);

        return [
            'module' => $module->only(['name', 'table_name', 'icon', 'description', 'settings', 'soft_deletes', 'has_timestamps']),
            'fields' => $module->fields
                ->map(fn (ModuleField $field) => $field->only([
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
                ]) + [
                    'relationship_module' => $field->relationshipModule?->table_name,
                ])
                ->values()
                ->all(),
        ];
    }
}
