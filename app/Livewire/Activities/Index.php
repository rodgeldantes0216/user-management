<?php

namespace App\Livewire\Activities;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    public ?int $selectedActivityId = null;

    public bool $showDetailDrawer = false;

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

    public function openDetail(int $activityId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('activities.view'), 403);

        $this->selectedActivityId = $activityId;
        $this->showDetailDrawer = true;
    }

    public function closeDetailDrawer(): void
    {
        $this->showDetailDrawer = false;
        $this->selectedActivityId = null;
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
            'selectedActivity' => $this->selectedActivity(),
            'selectedActivityMeta' => $this->selectedActivityMeta(),
            'selectedActivityContext' => $this->selectedActivityContext(),
            'selectedActivityChanges' => $this->selectedActivityChanges(),
            'selectedActivityAffectedRecord' => $this->selectedActivityAffectedRecord(),
        ]);
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.tailwind';
    }

    protected function selectedActivity(): ?ActivityLog
    {
        if (! $this->selectedActivityId) {
            return null;
        }

        return ActivityLog::query()->with('actor')->find($this->selectedActivityId);
    }

    protected function selectedActivityMeta(): array
    {
        $meta = $this->selectedActivity()?->meta ?? [];

        return collect($meta)
            ->except(['before', 'after', 'old', 'new', 'ip', 'user_agent', 'device'])
            ->map(fn ($value, string $key) => [
                'label' => Str::of($key)->replace('_', ' ')->headline()->toString(),
                'value' => $this->formatValue($value),
            ])
            ->values()
            ->all();
    }

    protected function selectedActivityContext(): array
    {
        $meta = $this->selectedActivity()?->meta ?? [];

        return collect([
            'IP address' => $meta['ip'] ?? null,
            'Device / agent' => $meta['device'] ?? $meta['user_agent'] ?? null,
        ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value, string $label) => [
                'label' => $label,
                'value' => $this->formatValue($value),
            ])
            ->values()
            ->all();
    }

    protected function selectedActivityChanges(): array
    {
        $meta = $this->selectedActivity()?->meta ?? [];
        $before = Arr::get($meta, 'before', Arr::get($meta, 'old', []));
        $after = Arr::get($meta, 'after', Arr::get($meta, 'new', []));

        if (! is_array($before) || ! is_array($after)) {
            return [];
        }

        return collect(array_unique([...array_keys($before), ...array_keys($after)]))
            ->map(fn (string $key) => [
                'label' => Str::of($key)->replace('_', ' ')->headline()->toString(),
                'before' => $this->formatValue($before[$key] ?? null),
                'after' => $this->formatValue($after[$key] ?? null),
                'changed' => ($before[$key] ?? null) !== ($after[$key] ?? null),
            ])
            ->values()
            ->all();
    }

    protected function selectedActivityAffectedRecord(): ?array
    {
        $activity = $this->selectedActivity();

        if (! $activity?->subject_type || ! $activity->subject_id) {
            return null;
        }

        $label = class_basename($activity->subject_type).' #'.$activity->subject_id;
        $exists = false;

        if (class_exists($activity->subject_type) && is_subclass_of($activity->subject_type, Model::class)) {
            $exists = (bool) $activity->subject_type::query()->whereKey($activity->subject_id)->exists();
        }

        return [
            'label' => $label,
            'status' => $exists ? 'Record found' : 'Record not found or deleted',
        ];
    }

    protected function formatValue($value): string
    {
        if ($value === null || $value === '') {
            return 'None';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
        }

        return (string) $value;
    }
}
