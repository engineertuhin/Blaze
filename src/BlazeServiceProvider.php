<?php


namespace LaravelBlaze\Builder\Commands;

use Illuminate\Console\Command;

class BuildCommand extends Command
{
    protected $signature = 'build';
    protected $description = 'Run the Laravel build script';

    public function handle()
    {
        $this->info("🚀 Running build script...");
        require base_path('vendor/blaze/builder/build.php');
        $this->info("✅ Build complete.");
    }
}
