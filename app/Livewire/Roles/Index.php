<?php

namespace App\Livewire\Roles;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Activity;
use App\Support\NotificationCenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Roles & Permissions')]
class Index extends Component
{
    use AuthorizesRequests;

    public bool $showFormModal = false;

    public ?int $editingRoleId = null;

    public string $name = '';

    public string $label = '';

    public array $selectedPermissions = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('roles.view'), 403);
    }

    public function create(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('roles.create'), 403);

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $roleId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('roles.update'), 403);

        $role = Role::query()->with('permissions')->findOrFail($roleId);

        $this->editingRoleId = $role->id;
        $this->name = $role->name;
        $this->label = $role->label;
        $this->selectedPermissions = $role->permissions->pluck('name')->all();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $permissionNames = Permission::query()->pluck('name')->all();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.($this->editingRoleId ?? 'NULL')],
            'label' => ['required', 'string', 'max:255'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['in:'.implode(',', $permissionNames)],
        ]);

        $role = Role::query()->updateOrCreate(
            ['id' => $this->editingRoleId],
            [
                'name' => $validated['name'],
                'label' => $validated['label'],
            ],
        );

        $role->permissions()->sync(
            Permission::query()
                ->whereIn('name', $validated['selectedPermissions'])
                ->pluck('id')
        );

        Activity::log($this->editingRoleId ? 'Updated role' : 'Created role', $role, [
            'role_name' => $role->name,
            'role_label' => $role->label,
            'permissions' => $validated['selectedPermissions'],
        ], auth()->user());
        NotificationCenter::notifyForModule(
            'roles',
            $this->editingRoleId ? 'Role updated' : 'Role created',
            auth()->user()->name.' '.($this->editingRoleId ? 'updated' : 'created').' the '.$role->label.' role.',
            'roles.view',
            auth()->user(),
            ['role_id' => $role->id]
        );

        session()->flash('status', $this->editingRoleId ? 'Role updated successfully.' : 'Role created successfully.');

        $this->closeFormModal();
    }

    public function delete(int $roleId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('roles.delete'), 403);

        $role = Role::query()->findOrFail($roleId);

        if (in_array($role->name, ['admin', 'user'], true)) {
            session()->flash('status', 'Default roles cannot be deleted.');

            return;
        }

        Activity::log('Deleted role', $role, [
            'role_name' => $role->name,
            'role_label' => $role->label,
        ], auth()->user());
        NotificationCenter::notifyForModule(
            'roles',
            'Role deleted',
            auth()->user()->name.' deleted the '.$role->label.' role.',
            'roles.view',
            auth()->user(),
            ['role_id' => $role->id]
        );

        $role->delete();
        session()->flash('status', 'Role deleted successfully.');
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.roles.index', [
            'roles' => Role::query()->with('permissions')->withCount('users')->orderBy('label')->get(),
            'permissions' => Permission::query()->orderBy('group')->orderBy('label')->get()->groupBy('group'),
        ]);
    }

    protected function resetForm(): void
    {
        $this->editingRoleId = null;
        $this->name = '';
        $this->label = '';
        $this->selectedPermissions = [];
    }
}
