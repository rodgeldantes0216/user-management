<?php

namespace App\Livewire\Users;

use App\Models\Role;
use App\Models\User;
use App\Support\Activity;
use App\Support\NotificationCenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('User Management')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingUserId = null;

    public ?int $deletingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $role = User::ROLE_USER;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        sleep(0.4);
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        sleep(0.4);
        $this->resetPage();
    }

    public function updatingPaginators($page, $pageName): void
    {
        sleep(0.4);
    }

    public function create(): void
    {
        $this->authorize('create', User::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $this->authorize('update', $user);

            $validated = $this->validate($this->rules($user));

            $payload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ];

            if ($validated['password'] !== '') {
                $payload['password'] = $validated['password'];
            }

            $user->update($payload);
            $user->syncRoleByName($validated['role']);
            Activity::log('Updated user', $user, [
                'target_name' => $user->name,
                'target_email' => $user->email,
                'role' => $validated['role'],
            ], auth()->user());
            NotificationCenter::notifyForModule(
                'users',
                'User updated',
                auth()->user()->name.' updated '.$user->name.'.',
                'users.view',
                auth()->user(),
                ['user_id' => $user->id]
            );

            session()->flash('status', 'User updated successfully.');
        } else {
            $this->authorize('create', User::class);

            $validated = $this->validate($this->rules());

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => $validated['password'],
            ]);

            $user->syncRoleByName($validated['role']);
            Activity::log('Created user', $user, [
                'target_name' => $user->name,
                'target_email' => $user->email,
                'role' => $validated['role'],
            ], auth()->user());
            NotificationCenter::notifyForModule(
                'users',
                'User created',
                auth()->user()->name.' created '.$user->name.'.',
                'users.view',
                auth()->user(),
                ['user_id' => $user->id]
            );

            session()->flash('status', 'User created successfully.');
        }

        $this->closeFormModal();
    }

    public function confirmDelete(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('delete', $user);

        $this->deletingUserId = $user->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $user = User::findOrFail($this->deletingUserId);
        $this->authorize('delete', $user);

        Activity::log('Deleted user', $user, [
            'target_name' => $user->name,
            'target_email' => $user->email,
            'role' => $user->primaryRoleName(),
        ], auth()->user());
        NotificationCenter::notifyForModule(
            'users',
            'User deleted',
            auth()->user()->name.' deleted '.$user->name.'.',
            'users.view',
            auth()->user(),
            ['user_id' => $user->id]
        );

        $user->delete();

        $this->closeDeleteModal();
        session()->flash('status', 'User deleted successfully.');
        $this->resetPage();
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
        $this->deletingUserId = null;
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($nested) {
                    $nested
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->roleFilter !== '', fn ($query) => $query->where('role', $this->roleFilter))
            ->latest()
            ->paginate(5);

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('label')->get(),
        ]);
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.tailwind';
    }

    protected function rules(?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'same:password_confirmation']
            : ['required', 'string', 'min:8', 'same:password_confirmation'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.($user?->id ?? 'NULL')],
            'role' => ['required', 'exists:roles,name'],
            'password' => $passwordRules,
        ];
    }

    protected function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->role = User::ROLE_USER;
        $this->password = '';
        $this->password_confirmation = '';
    }
}
