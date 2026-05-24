<?php

namespace App\Livewire\Users;

use App\Models\User;
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
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
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

            session()->flash('status', 'User updated successfully.');
        } else {
            $this->authorize('create', User::class);

            $validated = $this->validate($this->rules());

            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => $validated['password'],
            ]);

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
            ->paginate(8);

        return view('livewire.users.index', [
            'users' => $users,
        ]);
    }

    protected function rules(?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'same:password_confirmation']
            : ['required', 'string', 'min:8', 'same:password_confirmation'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.($user?->id ?? 'NULL')],
            'role' => ['required', 'in:'.implode(',', [User::ROLE_ADMIN, User::ROLE_USER])],
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
