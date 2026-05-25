<?php

namespace App\Livewire\SystemHealth;

use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('System Health')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('system-health.view'), 403);
    }

    public function render()
    {
        $checks = collect([
            $this->databaseCheck(),
            $this->cacheCheck(),
            $this->queueCheck(),
            $this->failedJobsCheck(),
            $this->mailCheck(),
            $this->storageLinkCheck(),
            $this->storageWritableCheck(),
            $this->appKeyCheck(),
            $this->debugModeCheck(),
            $this->environmentCheck(),
            $this->sessionSecurityCheck(),
            $this->lockfileCheck(),
        ]);

        return view('livewire.system-health.index', [
            'checks' => $checks,
            'healthyCount' => $checks->where('status', 'healthy')->count(),
            'warningCount' => $checks->where('status', 'warning')->count(),
            'criticalCount' => $checks->where('status', 'critical')->count(),
            'lastCheckedAt' => now(),
            'runtime' => [
                'PHP' => PHP_VERSION,
                'Laravel' => app()->version(),
                'Environment' => app()->environment(),
                'Queue' => config('queue.default'),
                'Cache' => config('cache.default'),
                'Mail' => config('mail.default'),
            ],
        ]);
    }

    protected function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return $this->check('Database connectivity', 'healthy', 'Database responded to a lightweight ping.', 'Data layer');
        } catch (Throwable $exception) {
            return $this->check('Database connectivity', 'critical', $exception->getMessage(), 'Data layer');
        }
    }

    protected function cacheCheck(): array
    {
        try {
            $key = 'system-health:'.str()->uuid();
            Cache::put($key, 'ok', now()->addMinute());
            $works = Cache::get($key) === 'ok';
            Cache::forget($key);

            return $this->check(
                'Cache round-trip',
                $works ? 'healthy' : 'critical',
                $works ? 'Cache write/read/delete completed.' : 'Cache did not return the value that was written.',
                'Infrastructure',
            );
        } catch (Throwable $exception) {
            return $this->check('Cache round-trip', 'critical', $exception->getMessage(), 'Infrastructure');
        }
    }

    protected function queueCheck(): array
    {
        $connection = config('queue.default');

        if ($connection !== 'database') {
            return $this->check('Queue status', 'warning', "Queue is using the {$connection} driver. This page can only count database queue jobs locally.", 'Queue');
        }

        if (! Schema::hasTable('jobs')) {
            return $this->check('Queue status', 'warning', 'The jobs table does not exist.', 'Queue');
        }

        $pending = DB::table('jobs')->whereNull('reserved_at')->count();
        $reserved = DB::table('jobs')->whereNotNull('reserved_at')->count();

        return $this->check(
            'Queue status',
            $reserved > 25 ? 'warning' : 'healthy',
            "{$pending} pending jobs and {$reserved} reserved jobs on the database queue.",
            'Queue',
        );
    }

    protected function failedJobsCheck(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return $this->check('Failed jobs', 'warning', 'The failed_jobs table does not exist.', 'Queue');
        }

        $failedJobs = DB::table('failed_jobs')->count();

        return $this->check(
            'Failed jobs',
            $failedJobs > 0 ? 'critical' : 'healthy',
            $failedJobs > 0 ? "{$failedJobs} failed jobs need review." : 'No failed jobs recorded.',
            'Queue',
        );
    }

    protected function mailCheck(): array
    {
        $mailer = config('mail.default');
        $configuredFrom = config('mail.from.address');
        $settingsFrom = Settings::all()['mail_from_address'] ?? null;
        $from = collect([$settingsFrom, $configuredFrom])
            ->filter(fn ($address) => filled($address) && ! $this->looksLikePlaceholderEmail($address))
            ->first() ?? $settingsFrom ?? $configuredFrom;
        $transport = config("mail.mailers.{$mailer}.transport", $mailer);

        if (! $from || $this->looksLikePlaceholderEmail($from)) {
            return $this->check('Mail configuration', 'warning', "Mailer {$mailer} uses {$transport}, but the from address looks unfinished.", 'Messaging');
        }

        return $this->check('Mail configuration', 'healthy', "Mailer {$mailer} uses {$transport} with {$from}.", 'Messaging');
    }

    protected function storageLinkCheck(): array
    {
        $path = public_path('storage');

        if (is_link($path) || File::exists($path)) {
            return $this->check('Public storage link', 'healthy', 'public/storage is available.', 'Filesystem');
        }

        return $this->check('Public storage link', 'warning', 'public/storage is missing. Run php artisan storage:link if public uploads should be served.', 'Filesystem');
    }

    protected function storageWritableCheck(): array
    {
        $paths = [
            storage_path('app'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        $blocked = collect($paths)
            ->filter(fn (string $path) => ! is_writable($path))
            ->map(fn (string $path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path))
            ->values();

        return $this->check(
            'Writable storage paths',
            $blocked->isEmpty() ? 'healthy' : 'critical',
            $blocked->isEmpty() ? 'Storage, logs, and cache paths are writable.' : 'Not writable: '.$blocked->join(', '),
            'Filesystem',
        );
    }

    protected function appKeyCheck(): array
    {
        $key = config('app.key');

        return $this->check(
            'Application key',
            $key ? 'healthy' : 'critical',
            $key ? 'APP_KEY is configured.' : 'APP_KEY is missing. Encrypted data and sessions are unsafe.',
            'Security',
        );
    }

    protected function debugModeCheck(): array
    {
        $debug = (bool) config('app.debug');

        return $this->check(
            'Debug mode',
            $debug ? 'warning' : 'healthy',
            $debug ? 'APP_DEBUG is enabled. Disable this before production.' : 'APP_DEBUG is disabled.',
            'Security',
        );
    }

    protected function environmentCheck(): array
    {
        $environment = app()->environment();

        return $this->check(
            'Application environment',
            $environment === 'production' ? 'healthy' : 'warning',
            "APP_ENV is {$environment}. Confirm this before deploying publicly.",
            'Security',
        );
    }

    protected function sessionSecurityCheck(): array
    {
        $secure = (bool) config('session.secure');
        $sameSite = config('session.same_site');

        if (app()->environment('production') && ! $secure) {
            return $this->check('Session cookie security', 'critical', 'Secure cookies are disabled in production.', 'Security');
        }

        return $this->check(
            'Session cookie security',
            $secure ? 'healthy' : 'warning',
            $secure ? "Secure cookies are enabled. SameSite is {$sameSite}." : "Secure cookies are disabled. SameSite is {$sameSite}.",
            'Security',
        );
    }

    protected function lockfileCheck(): array
    {
        return $this->check(
            'Dependency lockfiles',
            File::exists(base_path('composer.lock')) && File::exists(base_path('package-lock.json')) ? 'healthy' : 'warning',
            'composer.lock and package-lock.json should be committed so dependency versions are reproducible.',
            'Vulnerability hygiene',
        );
    }

    protected function check(string $name, string $status, string $message, string $group): array
    {
        return compact('name', 'status', 'message', 'group');
    }

    protected function looksLikePlaceholderEmail(string $email): bool
    {
        return str_contains($email, 'example.com')
            || str_contains($email, 'example.test')
            || str_starts_with($email, 'hello@');
    }
}
