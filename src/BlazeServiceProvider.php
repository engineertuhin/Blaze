<?php

namespace Blaze\Builder\Commands;

use Illuminate\Console\Command;

class BuildCommand extends Command
{
    protected $signature = 'blaze:build';
    protected $description = 'Run the Laravel build script';

    public function handle()
    {
        $this->info("🚀 Running build script...");
        require base_path('vendor/blaze/builder/build.php');
        $this->info("✅ Build complete.");
    }
}
