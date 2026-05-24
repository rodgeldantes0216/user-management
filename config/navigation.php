<?php

return [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'route' => 'dashboard',
        'permission' => 'dashboard.view',
    ],
    [
        'key' => 'users',
        'label' => 'Users',
        'route' => 'users.index',
        'permission' => 'users.view',
        'permissions' => [
            'users.create',
            'users.update',
            'users.delete',
        ],
    ],
    [
        'key' => 'roles',
        'label' => 'Roles & Permissions',
        'route' => 'roles.index',
        'permission' => 'roles.view',
        'permissions' => [
            'roles.create',
            'roles.update',
            'roles.delete',
        ],
    ],
    [
        'key' => 'activities',
        'label' => 'Audit Trail',
        'route' => 'activities.index',
        'permission' => 'activities.view',
        'permissions' => [
            'activities.delete',
        ],
    ],
    [
        'key' => 'settings',
        'label' => 'Settings',
        'route' => 'settings.index',
        'permission' => 'settings.view',
        'permissions' => [
            'settings.update',
        ],
    ],
    [
        'key' => 'notifications',
        'label' => 'Notification Center',
        'route' => 'notifications.index',
        'permission' => 'notifications.view',
        'permissions' => [
            'notifications.update',
            'notifications.delete',
        ],
    ],
    [
        'key' => 'modules',
        'label' => 'Module Builder',
        'route' => 'modules.builder',
        'permission' => 'modules.view',
        'permissions' => [
            'modules.create',
            'modules.update',
            'modules.delete',
        ],
    ],
];
