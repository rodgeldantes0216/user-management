<?php

namespace App\Livewire\Modules;

use App\Models\Module;
use App\Models\ModuleField;
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

    public function mount(string $module): void
    {
        $this->moduleDefinition = Module::query()->with('fields')->where('table_name', $module)->firstOrFail();

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
        if (! in_array($field, ['id', ...$this->moduleDefinition->fields->pluck('name')->all()], true)) {
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
            $this->form[$field->name] = in_array($field->type, ['password', 'file', 'image'], true) ? '' : ($record->{$field->name} ?? null);
        }

        $this->showFormModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermissionTo(
            $this->moduleDefinition->permissionName($this->editingRecordId ? 'update' : 'create')
        ), 403);

        $validated = $this->validate($this->rules())['form'];
        $payload = $this->payload($validated);

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

        if ($this->moduleDefinition->soft_deletes) {
            DB::table($this->moduleDefinition->table_name)->where('id', $this->deletingRecordId)->update(['deleted_at' => now()]);
        } else {
            DB::table($this->moduleDefinition->table_name)->where('id', $this->deletingRecordId)->delete();
        }

        Activity::log($this->activityAction('Deleted'), $this->moduleDefinition, [
            'module' => $this->moduleDefinition->name,
            'record_id' => $this->deletingRecordId,
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
            'filterFields' => $this->filterFields(),
        ]);
    }

    protected function sanitizeSortField(): string
    {
        $allowed = array_merge(['id'], $this->moduleDefinition->fields->pluck('name')->all());

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
            $fieldRules = $this->fieldRules($field);

            if ($field->validation_rules) {
                $fieldRules = array_merge($fieldRules, explode('|', $field->validation_rules));
            }

            $rules['form.'.$field->name] = $fieldRules;
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
                : $field->default_value;
        }
    }

    protected function searchableFields()
    {
        return $this->moduleDefinition->fields->whereIn('type', ['text', 'textarea', 'email', 'select', 'radio']);
    }

    protected function filterFields()
    {
        return $this->moduleDefinition->fields->whereIn('type', ['select', 'radio', 'checkbox', 'toggle']);
    }
}
