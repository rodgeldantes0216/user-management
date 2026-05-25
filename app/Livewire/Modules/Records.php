<?php

namespace App\Livewire\Modules;

use App\Models\Module;
use App\Models\ModuleField;
use App\Services\Modules\ConditionEvaluator;
use App\Services\Modules\FieldTypeRegistry;
use App\Services\Modules\FormulaEvaluator;
use App\Services\Modules\ValidationRuleBuilder;
use App\Support\Activity;
use App\Support\NotificationCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Module Records')]
class Records extends Component
{
    use WithFileUploads;
    use WithPagination;

    public Module $moduleDefinition;

    public string $search = '';

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    public array $filters = [];

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingRecordId = null;

    public ?int $deletingRecordId = null;

    public array $form = [];

    public array $uploads = [];

    public function updatedForm(): void
    {
        $this->applyComputedFields();
    }

    public function mount(string $module): void
    {
        $this->moduleDefinition = Module::query()
            ->with(['fields.relationshipModule.fields'])
            ->where('table_name', $module)
            ->firstOrFail();

        abort_unless(auth()->user()->hasPermissionTo($this->moduleDefinition->permissionName('view')), 403);
        abort_unless(Schema::hasTable($this->moduleDefinition->table_name), 404);

        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['id', ...$this->sortableFields()->pluck('name')->all()], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function create(): void
    {
        abort_unless(auth()->user()->hasPermissionTo($this->moduleDefinition->permissionName('create')), 403);

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $recordId): void
    {
        abort_unless(auth()->user()->hasPermissionTo($this->moduleDefinition->permissionName('update')), 403);

        $record = $this->baseQuery()->where('id', $recordId)->firstOrFail();

        $this->editingRecordId = $recordId;
        foreach ($this->moduleDefinition->fields as $field) {
            $value = in_array($field->type, ['password', 'file', 'image'], true) ? '' : ($record->{$field->name} ?? null);

            if (in_array($field->type, ['date_range'], true) && is_string($value)) {
                $value = json_decode($value, true) ?: ['start' => '', 'end' => ''];
            }

            $this->form[$field->name] = $value;
        }

        $this->applyComputedFields();

        $this->showFormModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermissionTo(
            $this->moduleDefinition->permissionName($this->editingRecordId ? 'update' : 'create')
        ), 403);

        $this->applyComputedFields();

        $validated = $this->validate($this->rules())['form'];
        $payload = $this->payload($validated);
        $oldValues = $this->editingRecordId
            ? (array) DB::table($this->moduleDefinition->table_name)->where('id', $this->editingRecordId)->first()
            : [];

        if ($this->editingRecordId) {
            DB::table($this->moduleDefinition->table_name)->where('id', $this->editingRecordId)->update([
                ...$payload,
                ...$this->timestampPayload(false),
            ]);
            $recordId = $this->editingRecordId;
            $action = $this->activityAction('Updated');
        } else {
            $recordId = DB::table($this->moduleDefinition->table_name)->insertGetId([
                ...$payload,
                ...$this->timestampPayload(true),
            ]);
            $action = $this->activityAction('Created');
        }

        Activity::log($action, $this->moduleDefinition, [
            'module' => $this->moduleDefinition->name,
            'record_id' => $recordId,
            'action_type' => $this->editingRecordId ? 'update' : 'create',
            'changed_fields' => array_keys($payload),
            'old_values' => $this->editingRecordId ? array_intersect_key($oldValues, $payload) : [],
            'new_values' => $payload,
        ], auth()->user());
        NotificationCenter::notifyForModule(
            $this->moduleDefinition->table_name,
            $this->moduleDefinition->name.' changed',
            auth()->user()->name.' changed a '.$this->moduleDefinition->name.' record.',
            $this->moduleDefinition->permissionName('view'),
            auth()->user(),
            ['record_id' => $recordId]
        );

        $this->closeFormModal();
        session()->flash('status', 'Record saved successfully.');
    }

    public function confirmDelete(int $recordId): void
    {
        abort_unless(auth()->user()->hasPermissionTo($this->moduleDefinition->permissionName('delete')), 403);

        $this->deletingRecordId = $recordId;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->hasPermissionTo($this->moduleDefinition->permissionName('delete')), 403);

        $oldValues = (array) DB::table($this->moduleDefinition->table_name)->where('id', $this->deletingRecordId)->first();

        if ($this->moduleDefinition->soft_deletes) {
            DB::table($this->moduleDefinition->table_name)->where('id', $this->deletingRecordId)->update(['deleted_at' => now()]);
        } else {
            DB::table($this->moduleDefinition->table_name)->where('id', $this->deletingRecordId)->delete();
        }

        Activity::log($this->activityAction('Deleted'), $this->moduleDefinition, [
            'module' => $this->moduleDefinition->name,
            'record_id' => $this->deletingRecordId,
            'action_type' => 'delete',
            'old_values' => $oldValues,
        ], auth()->user());

        $this->closeDeleteModal();
        $this->resetPage();
        session()->flash('status', 'Record deleted successfully.');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingRecordId = null;
    }

    public function render()
    {
        $this->sortField = $this->sanitizeSortField();
        $this->sortDirection = $this->sanitizeSortDirection();

        $records = $this->baseQuery()
            ->when($this->search !== '' && $this->searchableFields()->isNotEmpty(), function ($query) {
                $query->where(function ($nested) {
                    foreach ($this->searchableFields() as $field) {
                        $nested->orWhere($field->name, 'like', '%'.$this->search.'%');
                    }
                });
            })
            ->when($this->moduleDefinition->soft_deletes, fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->moduleDefinition->settings['pagination'] ?? 8);

        return view('livewire.modules.records', [
            'module' => $this->moduleDefinition,
            'records' => $records,
            'fields' => $this->moduleDefinition->fields,
            'listFields' => $this->listFields(),
            'filterFields' => $this->filterFields(),
            'relationshipDisplays' => $this->relationshipDisplays($records->items()),
            'canCreate' => auth()->user()->hasPermissionTo($this->moduleDefinition->permissionName('create')),
            'canUpdate' => auth()->user()->hasPermissionTo($this->moduleDefinition->permissionName('update')),
            'canDelete' => auth()->user()->hasPermissionTo($this->moduleDefinition->permissionName('delete')),
            'formGroups' => $this->formGroups(),
        ]);
    }

    protected function sanitizeSortField(): string
    {
        $allowed = array_merge(['id'], $this->sortableFields()->pluck('name')->all());

        return in_array($this->sortField, $allowed, true)
            ? $this->sortField
            : 'id';
    }

    protected function sanitizeSortDirection(): string
    {
        return $this->sortDirection === 'asc' ? 'asc' : 'desc';
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.tailwind';
    }

    protected function baseQuery()
    {
        $query = DB::table($this->moduleDefinition->table_name);
        $allowedFilters = $this->filterFields()->pluck('name')->all();

        foreach ($this->filters as $field => $value) {
            if (in_array($field, $allowedFilters, true) && $value !== '' && $value !== null) {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->moduleDefinition->fields as $field) {
            if (! $this->fieldIsVisible($field)) {
                continue;
            }

            $rules['form.'.$field->name] = app(ValidationRuleBuilder::class)->rulesFor($this->moduleDefinition, $field, $this->editingRecordId);
        }

        return $rules;
    }

    protected function fieldRules(ModuleField $field): array
    {
        $rules = [$field->required ? 'required' : 'nullable'];

        if ($field->unique) {
            $unique = Rule::unique($this->moduleDefinition->table_name, $field->name);

            if ($this->editingRecordId) {
                $unique->ignore($this->editingRecordId);
            }

            $rules[] = $unique;
        }

        return match ($field->type) {
            'email' => [...$rules, 'email', 'max:255'],
            'number' => [...$rules, 'numeric'],
            'password' => $this->editingRecordId ? ['nullable', 'string', 'min:8'] : [...$rules, 'string', 'min:8'],
            'select', 'radio' => [...$rules, Rule::in($field->optionList())],
            'checkbox', 'toggle' => [...$rules, 'boolean'],
            'date' => [...$rules, 'date'],
            'datetime' => [...$rules, 'date'],
            'relationship' => $this->relationshipRules($field, $rules),
            'file' => [
                $this->editingRecordId ? 'nullable' : ($field->required ? 'required' : 'nullable'),
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,csv,txt,zip',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain,application/zip',
            ],
            'image' => [
                $this->editingRecordId ? 'nullable' : ($field->required ? 'required' : 'nullable'),
                'image',
                'mimes:jpg,jpeg,png,gif,svg,webp',
                'mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp',
                'max:5120',
            ],
            default => [...$rules, 'string', 'max:2000'],
        };
    }

    protected function payload(array $validated): array
    {
        $payload = [];

        foreach ($this->moduleDefinition->fields as $field) {
            if ($field->isComputed() && $field->persistsComputedValue()) {
                $payload[$field->name] = app(FormulaEvaluator::class)->compute($field, $this->form);

                continue;
            }

            if (! $this->fieldIsVisible($field)) {
                if (! in_array($field->type, ['file', 'image', 'password'], true)) {
                    $payload[$field->name] = null;
                }

                continue;
            }

            $value = $validated[$field->name] ?? null;

            if ($field->type === 'password') {
                if ($value === null || $value === '') {
                    continue;
                }

                $payload[$field->name] = Hash::make($value);

                continue;
            }

            if (in_array($field->type, ['file', 'image'], true) && $value instanceof TemporaryUploadedFile) {
                $payload[$field->name] = $value->store('module-uploads/'.$this->moduleDefinition->table_name, 'public');

                continue;
            }

            if (in_array($field->type, ['file', 'image'], true) && $this->editingRecordId && ($value === null || $value === '')) {
                continue;
            }

            if ($field->type === 'date_range' && is_array($value)) {
                $payload[$field->name] = json_encode([
                    'start' => $value['start'] ?? null,
                    'end' => $value['end'] ?? null,
                ]);

                continue;
            }

            $payload[$field->name] = in_array($field->type, ['checkbox', 'toggle'], true) ? (bool) $value : $value;
        }

        return $payload;
    }

    protected function timestampPayload(bool $creating): array
    {
        if (! $this->moduleDefinition->has_timestamps) {
            return [];
        }

        return $creating
            ? ['created_at' => now(), 'updated_at' => now()]
            : ['updated_at' => now()];
    }

    protected function activityAction(string $verb): string
    {
        return $verb.' '.$this->moduleDefinition->name.' record';
    }

    protected function resetForm(): void
    {
        $this->editingRecordId = null;
        $this->form = [];

        foreach ($this->moduleDefinition->fields as $field) {
            $this->form[$field->name] = in_array($field->type, ['checkbox', 'toggle'], true)
                ? (bool) $field->default_value
                : ($field->type === 'date_range' ? ['start' => '', 'end' => ''] : $field->default_value);
        }

        $this->applyComputedFields();
    }

    protected function searchableFields()
    {
        return $this->moduleDefinition->fields
            ->where('searchable', true)
            ->whereIn('type', FieldTypeRegistry::SEARCHABLE_TYPES);
    }

    protected function filterFields()
    {
        return $this->moduleDefinition->fields
            ->where('filterable', true)
            ->whereIn('type', FieldTypeRegistry::FILTERABLE_TYPES);
    }

    protected function listFields()
    {
        return $this->moduleDefinition->fields->where('show_in_list', true);
    }

    protected function sortableFields()
    {
        return $this->moduleDefinition->fields->where('sortable', true);
    }

    protected function relationshipRules(ModuleField $field, array $rules): array
    {
        if (! $field->relationshipModule) {
            return [...$rules, 'integer'];
        }

        return [...$rules, 'integer', Rule::exists($field->relationshipModule->table_name, 'id')];
    }

    public function fieldIsVisible(ModuleField $field): bool
    {
        if (! $field->hasCondition()) {
            return $field->visible_in_form;
        }

        return $field->visible_in_form && app(ConditionEvaluator::class)->visible($field, $this->form);
    }

    protected function relationshipDisplays(array $records): array
    {
        $displays = [];

        foreach ($this->moduleDefinition->fields->where('type', 'relationship') as $field) {
            if (! $field->relationshipModule || ! $field->relationship_display_field) {
                continue;
            }

            $ids = collect($records)
                ->pluck($field->name)
                ->filter()
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                continue;
            }

            $displays[$field->name] = DB::table($field->relationshipModule->table_name)
                ->whereIn('id', $ids)
                ->pluck($field->relationship_display_field, 'id')
                ->all();
        }

        return $displays;
    }

    protected function applyComputedFields(): void
    {
        foreach ($this->moduleDefinition->fields as $field) {
            if (! $field->isComputed()) {
                continue;
            }

            $this->form[$field->name] = app(FormulaEvaluator::class)->compute($field, $this->form);
        }
    }

    protected function formGroups()
    {
        return $this->moduleDefinition->fields
            ->groupBy(fn (ModuleField $field) => $field->group_name ?: 'Details');
    }
}
