<?php

namespace App\Livewire\Modules;

use App\Models\Module;
use App\Models\ModuleField;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ModuleGenerator;
use App\Services\Modules\ModuleSnapshotService;
use App\Support\Activity;
use App\Support\ModuleNameGuard;
use App\Support\NotificationCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Module Builder')]
class Builder extends Component
{
    public string $name = '';

    public string $tableName = '';

    public string $description = '';

    public string $icon = 'square-stack';

    public bool $softDeletes = false;

    public bool $hasTimestamps = true;

    public array $fields = [];

    public array $rolePermissions = [];

    public string $exportJson = '';

    public string $importJson = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless(auth()->user()->hasPermissionTo('modules.view'), 403);

        $this->fields = [$this->blankField()];
        $this->seedRolePermissions();
    }

    public function updatedName(): void
    {
        if ($this->tableName === '') {
            try {
                $this->tableName = ModuleNameGuard::tableName($this->name);
            } catch (\Throwable) {
                $this->tableName = '';
            }
        }
    }

    public function addField(): void
    {
        $this->fields[] = $this->blankField(count($this->fields));
    }

    public function removeField(int $index): void
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function moveFieldUp(int $index): void
    {
        if ($index === 0 || ! isset($this->fields[$index])) {
            return;
        }

        [$this->fields[$index - 1], $this->fields[$index]] = [$this->fields[$index], $this->fields[$index - 1]];
    }

    public function moveFieldDown(int $index): void
    {
        if (! isset($this->fields[$index + 1])) {
            return;
        }

        [$this->fields[$index], $this->fields[$index + 1]] = [$this->fields[$index + 1], $this->fields[$index]];
    }

    public function addConditionRule(int $index): void
    {
        $this->fields[$index]['condition_config']['boolean'] ??= 'and';
        $this->fields[$index]['condition_config']['rules'] ??= [];
        $this->fields[$index]['condition_config']['groups'] ??= [];
        $this->fields[$index]['condition_config']['rules'][] = [
            'field' => '',
            'operator' => 'equals',
            'value' => '',
        ];
    }

    public function removeConditionRule(int $fieldIndex, int $ruleIndex): void
    {
        unset($this->fields[$fieldIndex]['condition_config']['rules'][$ruleIndex]);
        $this->fields[$fieldIndex]['condition_config']['rules'] = array_values($this->fields[$fieldIndex]['condition_config']['rules'] ?? []);
    }

    public function generate(ModuleGenerator $generator): void
    {
        abort_unless(auth()->user()->hasPermissionTo('modules.create'), 403);

        $validated = $this->validatePayload();

        try {
            $tableName = ModuleNameGuard::tableName($validated['tableName'] ?: $validated['name']);

            $sanitizedFields = collect($validated['fields'])
                ->map(function (array $field) {
                    $name = $field['name'] !== ''
                        ? ModuleNameGuard::fieldName($field['name'])
                        : ModuleNameGuard::fieldName($field['label']);

                    return [...$field, 'name' => $name];
                })
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            if ($exception instanceof ValidationException) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->addError($field, $message);
                    }
                }
            } else {
                $this->addError('name', 'Invalid module or field name.');
            }

            return;
        }

        if (Schema::hasTable($tableName) || Module::query()->where('table_name', $tableName)->exists()) {
            $this->addError('tableName', 'This table name is already used.');

            return;
        }

        if (collect($sanitizedFields)->pluck('name')->duplicates()->isNotEmpty()) {
            $this->addError('fields', 'Field names must be unique after sanitizing.');

            return;
        }

        DB::transaction(function () use ($validated, $tableName, $sanitizedFields, $generator) {
            $module = Module::query()->create([
                'name' => $validated['name'],
                'table_name' => $tableName,
                'icon' => $validated['icon'],
                'description' => $validated['description'],
                'soft_deletes' => $validated['softDeletes'],
                'has_timestamps' => $validated['hasTimestamps'],
                'settings' => [
                    'search' => true,
                    'pagination' => 8,
                    'sorting' => true,
                    'filters' => true,
                ],
            ]);

            foreach ($sanitizedFields as $index => $field) {
                $module->fields()->create([
                    'label' => $field['label'],
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'relationship_module_id' => $field['type'] === 'relationship' ? $field['relationship_module_id'] : null,
                    'relationship_display_field' => $field['type'] === 'relationship' ? $field['relationship_display_field'] : null,
                    'relationship_type' => $field['relationship_type'],
                    'required' => $field['required'],
                    'nullable' => ! $field['required'] || filled($field['condition_field']) || filled($field['condition_config']),
                    'unique' => $field['unique'],
                    'default_value' => $field['default_value'] ?: null,
                    'validation_rules' => $field['validation_rules'] ?: null,
                    'validation_config' => $this->validationConfig($field),
                    'placeholder' => $field['placeholder'] ?: null,
                    'options' => $this->optionsFromText($field['options_text'] ?? ''),
                    'condition_field' => $field['condition_field'] ?: null,
                    'condition_operator' => $field['condition_operator'] ?: null,
                    'condition_value' => $field['condition_value'] ?: null,
                    'condition_config' => $this->conditionConfig($field),
                    'computed_config' => $this->computedConfig($field),
                    'show_in_list' => $field['show_in_list'],
                    'visible_in_form' => $field['visible_in_form'],
                    'searchable' => $field['searchable'],
                    'filterable' => $field['filterable'],
                    'sortable' => $field['sortable'],
                    'group_name' => $field['group_name'] ?: null,
                    'group_type' => $field['group_type'],
                    'column_span' => $field['column_span'],
                    'sort_order' => $index,
                ]);
            }

            $generator->generate($module);
            $this->applyRolePermissions($module);

            Activity::log('Generated module', $module, [
                'module' => $module->name,
                'table_name' => $module->table_name,
            ], auth()->user());
            NotificationCenter::notifyForModule(
                'modules',
                'Module generated',
                auth()->user()->name.' generated the '.$module->name.' module.',
                'modules.view',
                auth()->user(),
                ['module_id' => $module->id]
            );
        });

        $this->resetBuilder();
        session()->flash('status', 'Module generated successfully.');
    }

    public function regenerate(int $moduleId, ModuleGenerator $generator): void
    {
        abort_unless(auth()->user()->hasPermissionTo('modules.update'), 403);

        $module = Module::query()->with('fields')->findOrFail($moduleId);
        $generator->generate($module);

        Activity::log('Regenerated module', $module, [
            'module' => $module->name,
            'table_name' => $module->table_name,
        ], auth()->user());

        session()->flash('status', 'Module artifacts regenerated safely.');
    }

    public function snapshot(int $moduleId, ModuleSnapshotService $snapshots): void
    {
        abort_unless(auth()->user()->hasPermissionTo('modules.update'), 403);

        $snapshots->snapshot(
            Module::query()->with('fields')->findOrFail($moduleId),
            auth()->user(),
            'Manual snapshot'
        );

        session()->flash('status', 'Module snapshot saved.');
    }

    public function export(int $moduleId, ModuleGenerator $generator): void
    {
        abort_unless(auth()->user()->hasPermissionTo('modules.view'), 403);

        $this->exportJson = json_encode(
            $generator->export(Module::query()->with('fields')->findOrFail($moduleId)),
            JSON_PRETTY_PRINT
        );
    }

    public function import(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('modules.create'), 403);

        $payload = json_decode($this->importJson, true);

        if (! is_array($payload) || ! isset($payload['module'], $payload['fields'])) {
            $this->addError('importJson', 'Paste a valid module export JSON payload.');

            return;
        }

        $this->name = (string) ($payload['module']['name'] ?? '');
        $this->tableName = (string) ($payload['module']['table_name'] ?? '');
        $this->icon = (string) ($payload['module']['icon'] ?? 'square-stack');
        $this->description = (string) ($payload['module']['description'] ?? '');
        $this->softDeletes = (bool) ($payload['module']['soft_deletes'] ?? false);
        $this->hasTimestamps = (bool) ($payload['module']['has_timestamps'] ?? true);
        $this->seedRolePermissions($payload['module']['settings']['role_permissions'] ?? []);
        $this->fields = collect($payload['fields'])
            ->map(function (array $field, int $index) {
                $relationshipModule = Module::query()
                    ->where('table_name', (string) ($field['relationship_module'] ?? ''))
                    ->first();

                return [
                    ...$this->blankField($index),
                    'label' => (string) ($field['label'] ?? ''),
                    'name' => (string) ($field['name'] ?? ''),
                    'type' => (string) ($field['type'] ?? 'text'),
                    'relationship_module_id' => (string) ($field['relationship_module_id'] ?? $relationshipModule?->id ?? ''),
                    'relationship_display_field' => (string) ($field['relationship_display_field'] ?? ''),
                    'relationship_type' => (string) ($field['relationship_type'] ?? 'belongs_to'),
                    'required' => (bool) ($field['required'] ?? false),
                    'unique' => (bool) ($field['unique'] ?? false),
                    'default_value' => (string) ($field['default_value'] ?? ''),
                    'validation_rules' => (string) ($field['validation_rules'] ?? ''),
                    'validation_config' => (array) ($field['validation_config'] ?? []),
                    'placeholder' => (string) ($field['placeholder'] ?? ''),
                    'options_text' => collect($field['options'] ?? [])->implode(PHP_EOL),
                    'condition_field' => (string) ($field['condition_field'] ?? ''),
                    'condition_operator' => (string) ($field['condition_operator'] ?? ''),
                    'condition_value' => (string) ($field['condition_value'] ?? ''),
                    'condition_config' => (array) ($field['condition_config'] ?? []),
                    'computed_config' => (array) ($field['computed_config'] ?? []),
                    'show_in_list' => (bool) ($field['show_in_list'] ?? true),
                    'visible_in_form' => (bool) ($field['visible_in_form'] ?? true),
                    'searchable' => (bool) ($field['searchable'] ?? true),
                    'filterable' => (bool) ($field['filterable'] ?? false),
                    'sortable' => (bool) ($field['sortable'] ?? true),
                    'group_name' => (string) ($field['group_name'] ?? ''),
                    'group_type' => (string) ($field['group_type'] ?? 'section'),
                    'column_span' => (int) ($field['column_span'] ?? 1),
                ];
            })
            ->values()
            ->all();

        try {
            if ($this->tableName !== '') {
                ModuleNameGuard::tableName($this->tableName);
            }

            collect($this->fields)->each(function (array $field) {
                if ($field['name'] !== '') {
                    ModuleNameGuard::fieldName($field['name']);
                } elseif ($field['label'] !== '') {
                    ModuleNameGuard::fieldName($field['label']);
                }
            });
        } catch (\Throwable $exception) {
            $this->addError('importJson', 'Imported module JSON contains invalid names. Correct the field or module names before generating.');
        }
    }

    public function render()
    {
        return view('livewire.modules.builder', [
            'modules' => Module::query()->withCount('fields')->latest()->get(),
            'availableModules' => Module::query()->with('fields')->whereNotNull('generated_at')->orderBy('name')->get(),
            'fieldTypes' => ModuleField::TYPES,
            'roles' => Role::query()->orderBy('label')->get(),
            'abilities' => Module::PERMISSION_ABILITIES,
        ]);
    }

    protected function validatePayload(): array
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'tableName' => ['nullable', 'string', 'max:63', 'regex:/^[a-z][a-z0-9_]{1,62}$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['required', 'string', 'max:80'],
            'softDeletes' => ['boolean'],
            'hasTimestamps' => ['boolean'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.name' => ['nullable', 'string', 'max:63', 'regex:/^[a-z][a-z0-9_]{1,62}$/'],
            'fields.*.type' => ['required', Rule::in(ModuleField::TYPES)],
            'fields.*.relationship_module_id' => ['nullable', 'integer', 'exists:modules,id'],
            'fields.*.relationship_display_field' => ['nullable', 'string', 'max:63', 'regex:/^[a-z][a-z0-9_]{1,62}$/'],
            'fields.*.relationship_type' => ['nullable', Rule::in(['belongs_to', 'has_many', 'belongs_to_many'])],
            'fields.*.required' => ['boolean'],
            'fields.*.unique' => ['boolean'],
            'fields.*.default_value' => ['nullable', 'string', 'max:2000'],
            'fields.*.validation_rules' => ['nullable', 'string', 'max:2000'],
            'fields.*.validation_config' => ['nullable', 'array'],
            'fields.*.validation_config.min' => ['nullable', 'string', 'max:50'],
            'fields.*.validation_config.max' => ['nullable', 'string', 'max:50'],
            'fields.*.validation_config.regex' => ['nullable', 'string', 'max:500'],
            'fields.*.validation_config.email' => ['boolean'],
            'fields.*.validation_config.numeric' => ['boolean'],
            'fields.*.validation_config.file_mimes' => ['nullable', 'string', 'max:255'],
            'fields.*.validation_config.max_file_size' => ['nullable', 'string', 'max:50'],
            'fields.*.validation_config.custom' => ['nullable', 'string', 'max:2000'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.options_text' => ['nullable', 'string', 'max:5000'],
            'fields.*.condition_field' => ['nullable', 'string', 'max:63', 'regex:/^[a-z][a-z0-9_]{1,62}$/'],
            'fields.*.condition_operator' => ['nullable', Rule::in(['equals', 'not_equals', 'contains', 'greater_than', 'less_than', 'empty', 'not_empty'])],
            'fields.*.condition_value' => ['nullable', 'string', 'max:2000'],
            'fields.*.condition_config' => ['nullable', 'array'],
            'fields.*.condition_config.boolean' => ['nullable', Rule::in(['and', 'or'])],
            'fields.*.condition_config.rules' => ['nullable', 'array'],
            'fields.*.condition_config.rules.*.field' => ['nullable', 'string', 'max:63', 'regex:/^[a-z][a-z0-9_]{1,62}$/'],
            'fields.*.condition_config.rules.*.operator' => ['nullable', Rule::in(['equals', 'not_equals', 'contains', 'greater_than', 'less_than', 'empty', 'not_empty'])],
            'fields.*.condition_config.rules.*.value' => ['nullable', 'string', 'max:2000'],
            'fields.*.computed_config' => ['nullable', 'array'],
            'fields.*.computed_config.expression' => ['nullable', 'string', 'max:1000'],
            'fields.*.computed_config.mode' => ['nullable', Rule::in(['template', 'math'])],
            'fields.*.computed_config.persist' => ['boolean'],
            'fields.*.computed_config.readonly' => ['boolean'],
            'fields.*.show_in_list' => ['boolean'],
            'fields.*.visible_in_form' => ['boolean'],
            'fields.*.searchable' => ['boolean'],
            'fields.*.filterable' => ['boolean'],
            'fields.*.sortable' => ['boolean'],
            'fields.*.group_name' => ['nullable', 'string', 'max:80'],
            'fields.*.group_type' => ['nullable', Rule::in(['section', 'tab', 'accordion'])],
            'fields.*.column_span' => ['nullable', 'integer', 'min:1', 'max:2'],
        ]);

        $validated['fields'] = collect($validated['fields'])
            ->map(fn (array $field, int $index) => [...$this->blankField($index), ...$field])
            ->values()
            ->all();

        $errors = [];

        foreach ($validated['fields'] as $index => $field) {
            if ($field['type'] === 'relationship' && (empty($field['relationship_module_id']) || empty($field['relationship_display_field']))) {
                $errors["fields.$index.relationship_module_id"][] = 'Choose a module and display field for relationship fields.';
            }

            if (empty($field['condition_field']) !== empty($field['condition_operator'])) {
                $errors["fields.$index.condition_field"][] = 'Choose both a condition field and condition operator.';
            }
        }

        $errors = [...$errors, ...$this->fieldMetadataErrors($validated['fields'])];

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    protected function blankField(int $index = 0): array
    {
        return [
            'label' => '',
            'name' => '',
            'type' => 'text',
            'relationship_module_id' => '',
            'relationship_display_field' => '',
            'relationship_type' => 'belongs_to',
            'required' => false,
            'unique' => false,
            'default_value' => '',
            'validation_rules' => '',
            'validation_config' => [
                'min' => '',
                'max' => '',
                'regex' => '',
                'email' => false,
                'numeric' => false,
                'file_mimes' => '',
                'max_file_size' => '',
                'custom' => '',
            ],
            'placeholder' => '',
            'options_text' => '',
            'condition_field' => '',
            'condition_operator' => '',
            'condition_value' => '',
            'condition_config' => [
                'boolean' => 'and',
                'rules' => [],
                'groups' => [],
            ],
            'computed_config' => [
                'expression' => '',
                'mode' => 'template',
                'persist' => true,
                'readonly' => true,
            ],
            'show_in_list' => true,
            'visible_in_form' => true,
            'searchable' => true,
            'filterable' => false,
            'sortable' => true,
            'group_name' => '',
            'group_type' => 'section',
            'column_span' => 1,
            'sort_order' => $index,
        ];
    }

    protected function optionsFromText(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn (string $option) => trim($option))
            ->filter()
            ->values()
            ->all();
    }

    protected function resetBuilder(): void
    {
        $this->name = '';
        $this->tableName = '';
        $this->description = '';
        $this->icon = 'square-stack';
        $this->softDeletes = false;
        $this->hasTimestamps = true;
        $this->fields = [$this->blankField()];
        $this->seedRolePermissions();
        $this->importJson = '';
    }

    protected function fieldMetadataErrors(array $fields): array
    {
        $names = collect($fields)
            ->pluck('name')
            ->filter()
            ->values();

        $errors = [];

        foreach ($fields as $index => $field) {
            if ($field['condition_field'] && ! $names->contains($field['condition_field'])) {
                $errors["fields.$index.condition_field"][] = 'Choose one of this module\'s named fields.';
            }

            foreach (($field['condition_config']['rules'] ?? []) as $ruleIndex => $rule) {
                if (filled($rule['field'] ?? null) && ! $names->contains($rule['field'])) {
                    $errors["fields.$index.condition_config.rules.$ruleIndex.field"][] = 'Choose one of this module\'s named fields.';
                }
            }

            if ($field['type'] !== 'relationship') {
                continue;
            }

            $relatedModule = Module::query()->with('fields')->find($field['relationship_module_id']);

            if (! $relatedModule?->fields->contains('name', $field['relationship_display_field'])) {
                $errors["fields.$index.relationship_display_field"][] = 'Choose a display field from the related module.';
            }
        }

        return $errors;
    }

    protected function validationConfig(array $field): array
    {
        return collect($field['validation_config'] ?? [])
            ->filter(fn ($value) => $value !== null && $value !== '' && $value !== false)
            ->all();
    }

    protected function conditionConfig(array $field): ?array
    {
        $configured = $field['condition_config'] ?? [];
        $rules = collect($configured['rules'] ?? [])
            ->filter(fn (array $rule) => filled($rule['field'] ?? null) && filled($rule['operator'] ?? null))
            ->values()
            ->all();

        if ($rules !== []) {
            return [
                'boolean' => $configured['boolean'] ?? 'and',
                'rules' => $rules,
                'groups' => $configured['groups'] ?? [],
            ];
        }

        if (! filled($field['condition_field'] ?? null) || ! filled($field['condition_operator'] ?? null)) {
            return null;
        }

        return [
            'boolean' => 'and',
            'rules' => [[
                'field' => $field['condition_field'],
                'operator' => $field['condition_operator'],
                'value' => $field['condition_value'] ?? null,
            ]],
            'groups' => [],
        ];
    }

    protected function computedConfig(array $field): ?array
    {
        $config = $field['computed_config'] ?? [];

        if (! filled($config['expression'] ?? null)) {
            return null;
        }

        return [
            'expression' => $config['expression'],
            'mode' => $config['mode'] ?? 'template',
            'persist' => (bool) ($config['persist'] ?? true),
            'readonly' => (bool) ($config['readonly'] ?? true),
        ];
    }

    protected function seedRolePermissions(array $imported = []): void
    {
        $this->rolePermissions = Role::query()
            ->orderBy('label')
            ->get()
            ->mapWithKeys(function (Role $role) use ($imported) {
                $defaults = $role->name === 'admin'
                    ? collect(Module::PERMISSION_ABILITIES)->mapWithKeys(fn (string $ability) => [$ability => true])->all()
                    : collect(Module::PERMISSION_ABILITIES)->mapWithKeys(fn (string $ability) => [$ability => false])->all();

                $roleImport = $imported[$role->name] ?? [];

                if (array_is_list($roleImport)) {
                    $roleImport = collect($roleImport)->mapWithKeys(fn (string $ability) => [$ability => true])->all();
                }

                return [
                    $role->id => [
                        'role_name' => $role->name,
                        ...collect(Module::PERMISSION_ABILITIES)
                            ->mapWithKeys(fn (string $ability) => [$ability => (bool) ($roleImport[$ability] ?? $defaults[$ability])])
                            ->all(),
                    ],
                ];
            })
            ->all();
    }

    protected function rolePermissionSettings(): array
    {
        return collect($this->rolePermissions)
            ->mapWithKeys(function (array $permissions) {
                $roleName = $permissions['role_name'] ?? null;

                if (! $roleName) {
                    return [];
                }

                return [
                    $roleName => collect(Module::PERMISSION_ABILITIES)
                        ->filter(fn (string $ability) => (bool) ($permissions[$ability] ?? false))
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    protected function applyRolePermissions(Module $module): void
    {
        $permissionIds = Permission::query()
            ->whereIn('name', collect(Module::PERMISSION_ABILITIES)->map(fn (string $ability) => $module->permissionName($ability)))
            ->pluck('id', 'name');

        foreach ($this->rolePermissions as $roleId => $permissions) {
            $role = Role::query()->find($roleId);

            if (! $role) {
                continue;
            }

            $selectedIds = collect(Module::PERMISSION_ABILITIES)
                ->filter(fn (string $ability) => (bool) ($permissions[$ability] ?? false))
                ->map(fn (string $ability) => $permissionIds[$module->permissionName($ability)] ?? null)
                ->filter()
                ->values()
                ->all();

            $existingIds = $role->permissions()
                ->whereNotIn('permissions.id', $permissionIds->values()->all())
                ->pluck('permissions.id')
                ->all();

            $role->permissions()->sync(array_values(array_unique([...$existingIds, ...$selectedIds])));
        }

        $module->forceFill([
            'settings' => [
                ...($module->settings ?? []),
                'role_permissions' => $this->rolePermissionSettings(),
            ],
        ])->save();
    }
}
