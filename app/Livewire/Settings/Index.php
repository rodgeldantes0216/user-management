<?php

namespace App\Livewire\Settings;

use App\Support\Activity;
use App\Support\NotificationCenter;
use App\Support\Settings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Settings Management')]
class Index extends Component
{
    public string $site_name = '';

    public string $site_logo = '';

    public string $mail_from_name = '';

    public string $mail_from_address = '';

    public bool $feature_registration_enabled = true;

    public bool $feature_audit_enabled = true;

    public bool $feature_notifications_enabled = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('settings.view'), 403);

        $settings = Settings::all();

        $this->site_name = $settings['site_name'];
        $this->site_logo = $settings['site_logo'];
        $this->mail_from_name = $settings['mail_from_name'];
        $this->mail_from_address = $settings['mail_from_address'];
        $this->feature_registration_enabled = (bool) $settings['feature_registration_enabled'];
        $this->feature_audit_enabled = (bool) $settings['feature_audit_enabled'];
        $this->feature_notifications_enabled = (bool) $settings['feature_notifications_enabled'];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('settings.update'), 403);
        $before = Settings::all();

        $validated = $this->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_logo' => ['nullable', 'string', 'max:2048'],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'feature_registration_enabled' => ['boolean'],
            'feature_audit_enabled' => ['boolean'],
            'feature_notifications_enabled' => ['boolean'],
        ]);

        Settings::save($validated);

        Activity::log('Updated settings', null, [
            'before' => $before,
            'after' => $validated,
        ], auth()->user());
        NotificationCenter::notifyForModule(
            'settings',
            'Settings updated',
            auth()->user()->name.' updated the application settings.',
            'settings.view',
            auth()->user()
        );

        session()->flash('status', 'Settings updated successfully.');
    }

    public function render()
    {
        return view('livewire.settings.index');
    }
}
