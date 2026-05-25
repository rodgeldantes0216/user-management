<?php

namespace App\Livewire\Profile;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('My Profile')]
class Index extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public ?string $phone = null;

    public ?string $location = null;

    public string $bio = '';

    public bool $email_notifications = true;

    public bool $show_activity_summary = true;

    public ?TemporaryUploadedFile $profile_picture = null;

    public ?string $currentPictureUrl = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->username = $user->username ?? '';
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->location = $user->location;
        $this->bio = $user->bio ?? '';
        $this->email_notifications = $user->preferences['email_notifications'] ?? true;
        $this->show_activity_summary = $user->preferences['show_activity_summary'] ?? true;
        $this->currentPictureUrl = $user->profile_picture_url;
    }

    public function updatedProfilePicture(): void
    {
        $this->validateOnly('profile_picture', [
            'profile_picture' => ['image', 'mimes:jpg,jpeg,png,svg,webp', 'max:5120'],
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'min:3', 'max:40', 'unique:users,username,'.auth()->id()],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.auth()->id()],
            'phone' => ['nullable', 'string', 'max:25'],
            'location' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'email_notifications' => ['boolean'],
            'show_activity_summary' => ['boolean'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:5120'],
        ]);

        $user = auth()->user();

        if ($validated['profile_picture'] instanceof TemporaryUploadedFile) {
            $picturePath = $validated['profile_picture']->store('user-profiles/'.$user->id, 'public');
            $user->profile_picture = $picturePath;
            $this->currentPictureUrl = Storage::disk('public')->url($picturePath);
        }

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'location' => $validated['location'],
            'bio' => $validated['bio'],
            'preferences' => [
                'email_notifications' => $validated['email_notifications'],
                'show_activity_summary' => $validated['show_activity_summary'],
            ],
        ]);

        $user->save();

        session()->flash('status', 'Profile updated successfully.');
    }

    public function render()
    {
        return view('livewire.profile.index', [
            'activityLogs' => ActivityLog::query()
                ->where('actor_id', auth()->id())
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
