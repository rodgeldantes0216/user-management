<?php

namespace App\Livewire\Modules;

use App\Models\Module;
use App\Models\ModuleField;
use App\Services\ModuleGenerator;
use App\Support\Activity;
use App\Support\ModuleNameGuard;
use App\Support\NotificationCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
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

    public string $exportJson = '';

    public string $importJson = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless(auth()->user()->hasPermissionTo('modules.view'), 403);

        $this->fields = [$this->blankField()];
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
            if ($exception instanceof \Illuminate\Validation\ValidationException) {
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
                    'required' => $field['required'],
                    'nullable' => ! $field['required'],
                    'unique' => $field['unique'],
                    'default_value' => $field['default_value'] ?: null,
                    'validation_rules' => $field['validation_rules'] ?: null,
                    'placeholder' => $field['placeholder'] ?: null,
                    'options' => $this->optionsFromText($field['options_text'] ?? ''),
                    'sort_order' => $index,
                ]);
            }

            $generator->generate($module);

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
        $this->fields = collect($payload['fields'])
            ->map(function (array $field, int $index) {
                return [
                    ...$this->blankField($index),
                    'label' => (string) ($field['label'] ?? ''),
                    'name' => (string) ($field['name'] ?? ''),
                    'type' => (string) ($field['type'] ?? 'text'),
                    'required' => (bool) ($field['required'] ?? false),
                    'unique' => (bool) ($field['unique'] ?? false),
                    'default_value' => (string) ($field['default_value'] ?? ''),
                    'validation_rules' => (string) ($field['validation_rules'] ?? ''),
                    'placeholder' => (string) ($field['placeholder'] ?? ''),
                    'options_text' => collect($field['options'] ?? [])->implode(PHP_EOL),
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
            'fieldTypes' => ModuleField::TYPES,
        ]);
    }

    protected function validatePayload(): array
    {
        return $this->validate([
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
            'fields.*.required' => ['boolean'],
            'fields.*.unique' => ['boolean'],
            'fields.*.default_value' => ['nullable', 'string', 'max:2000'],
            'fields.*.validation_rules' => ['nullable', 'string', 'max:2000'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.options_text' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    protected function blankField(int $index = 0): array
    {
        return [
            'label' => '',
            'name' => '',
            'type' => 'text',
            'required' => false,
            'unique' => false,
            'default_value' => '',
            'validation_rules' => '',
            'placeholder' => '',
            'options_text' => '',
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
        $this->importJson = '';
    }
}
