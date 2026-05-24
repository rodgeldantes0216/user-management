<?php

namespace App\Support;

use App\Models\Setting;

class Settings
{
    public static function all(): array
    {
        return [
            'site_name' => Setting::getValue('site_name', config('app.name')),
            'site_logo' => Setting::getValue('site_logo', ''),
            'mail_from_name' => Setting::getValue('mail_from_name', config('mail.from.name')),
            'mail_from_address' => Setting::getValue('mail_from_address', config('mail.from.address')),
            'feature_registration_enabled' => Setting::getValue('feature_registration_enabled', true),
            'feature_audit_enabled' => Setting::getValue('feature_audit_enabled', true),
            'feature_notifications_enabled' => Setting::getValue('feature_notifications_enabled', true),
        ];
    }

    public static function save(array $settings): void
    {
        Setting::put('site_name', $settings['site_name']);
        Setting::put('site_logo', $settings['site_logo']);
        Setting::put('mail_from_name', $settings['mail_from_name']);
        Setting::put('mail_from_address', $settings['mail_from_address']);
        Setting::put('feature_registration_enabled', $settings['feature_registration_enabled'], 'boolean');
        Setting::put('feature_audit_enabled', $settings['feature_audit_enabled'], 'boolean');
        Setting::put('feature_notifications_enabled', $settings['feature_notifications_enabled'], 'boolean');
    }
}
