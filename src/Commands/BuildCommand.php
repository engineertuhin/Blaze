<?php

namespace LaravelBlaze\Builder\Commands;

use Illuminate\Console\Command;

class BuildCommand extends Command
{
    protected $signature = 'build';
    protected $description = 'Build and optimize your Laravel app based on blaze.json';

    public function handle()
    {
        $this->info("🚀 Starting Laravel build process...");

        require base_path('vendor/laravel-blaze/builder/build.php');

        $this->info("✅ Build completed successfully.");
    }
}
