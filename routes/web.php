<?php

use App\Livewire\Activities\Index as ActivitiesIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard;
use App\Livewire\Modules\Builder as ModuleBuilder;
use App\Livewire\Modules\Records as ModuleRecords;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Profile\Index as ProfileIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Roles\Index as RolesIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\SystemHealth\Index as SystemHealthIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/users', UsersIndex::class)
        ->middleware('permission:users.view')
        ->name('users.index');

    Route::get('/roles', RolesIndex::class)
        ->middleware('permission:roles.view')
        ->name('roles.index');

    Route::get('/activities', ActivitiesIndex::class)
        ->middleware('permission:activities.view')
        ->name('activities.index');

    Route::get('/reports', ReportsIndex::class)
        ->middleware('permission:reports.view')
        ->name('reports.index');

    Route::get('/system-health', SystemHealthIndex::class)
        ->middleware('permission:system-health.view')
        ->name('system-health.index');

    Route::get('/settings', SettingsIndex::class)
        ->middleware('permission:settings.view')
        ->name('settings.index');

    Route::get('/notifications', NotificationsIndex::class)
        ->middleware('permission:notifications.view')
        ->name('notifications.index');

    Route::get('/profile', ProfileIndex::class)
        ->name('profile.index');

    Route::get('/modules/builder', ModuleBuilder::class)
        ->middleware('permission:modules.view')
        ->name('modules.builder');

    Route::get('/modules/{module}', ModuleRecords::class)
        ->name('modules.records');

    Route::post('/logout', function (Request $request) {
        $request->user()?->forceFill([
            'logged_out_at' => now(),
        ])->saveQuietly();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
