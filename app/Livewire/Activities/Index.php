<?php

namespace App\Livewire\Activities;

use App\Models\ActivityLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Audit Trail')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public ?int $deletingActivityId = null;

    public bool $showDeleteModal = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('activities.view'), 403);
    }

    public function updatingSearch(): void
    {
        sleep(0.4);
        $this->resetPage();
    }

    public function updatingPaginators($page, $pageName): void
    {
        sleep(0.4);
    }

    public function confirmDelete(int $activityId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('activities.delete'), 403);

        $this->deletingActivityId = $activityId;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('activities.delete'), 403);

        $activity = ActivityLog::query()->findOrFail($this->deletingActivityId);
        $activity->delete();

        $this->closeDeleteModal();
        session()->flash('status', 'Activity log deleted successfully.');
        $this->resetPage();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingActivityId = null;
    }

    public function render()
    {
        $activities = ActivityLog::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($nested) {
                    $nested
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('action', 'like', '%'.$this->search.'%')
                        ->orWhere('meta', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(8);

        return view('livewire.activities.index', [
            'activities' => $activities,
        ]);
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.tailwind';
    }
}
