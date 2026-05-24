<?php

namespace App\Livewire\Notifications;

use App\Models\AppNotification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Notification Center')]
class Index extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('notifications.view'), 403);
    }

    public function updatingFilter(): void
    {
        usleep(1000000);
        $this->resetPage();
    }

    public function updatingPaginators($page, $pageName): void
    {
        usleep(1000000);
    }

    public function markAsRead(int $notificationId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('notifications.update'), 403);

        $this->notificationQuery()->findOrFail($notificationId)->update([
            'read_at' => now(),
        ]);
    }

    public function markAsUnread(int $notificationId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('notifications.update'), 403);

        $this->notificationQuery()->findOrFail($notificationId)->update([
            'read_at' => null,
        ]);
    }

    public function markAllAsRead(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('notifications.update'), 403);

        $this->notificationQuery()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function delete(int $notificationId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('notifications.delete'), 403);

        $this->notificationQuery()->findOrFail($notificationId)->delete();
        session()->flash('status', 'Notification deleted successfully.');
        $this->resetPage();
    }

    public function render()
    {
        $notifications = $this->notificationQuery()
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($this->filter === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate(8);

        return view('livewire.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $this->notificationQuery()->whereNull('read_at')->count(),
        ]);
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.tailwind';
    }

    protected function notificationQuery()
    {
        return AppNotification::query()->where('user_id', auth()->id());
    }
}
