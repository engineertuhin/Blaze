<?php

namespace LaravelBlaze\Builder;

use Illuminate\Support\ServiceProvider;
use LaravelBlaze\Builder\Commands\BuildCommand;

class BlazeServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register the command
        $this->commands([
            BuildCommand::class,
        ]);
    }

    public function boot()
    {
        // Publish blaze.json to project root
        $this->publishes([
            __DIR__ . '/../blaze.json' => base_path('blaze.json'),
        ], 'blaze-config');
    }
}
