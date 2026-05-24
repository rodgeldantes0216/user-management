<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('boom', function () {
    $commands = [
        'config:cache',
        'cache:clear',
        'view:cache',
        'view:clear',
        'optimize',
        'optimize:clear',
    ];

    foreach ($commands as $command) {
        $this->components->info("Running php artisan {$command}");
        $this->call($command);
    }

    $this->components->success('Boom complete.');
})->purpose('Run the application cache, view, and optimize refresh commands');
