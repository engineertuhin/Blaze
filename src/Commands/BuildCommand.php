<?php


namespace Blaze\Builder\Commands;

use Illuminate\Console\Command;
use Blaze\Builder\Helpers\Runner;

class BuildCommand extends Command
{
protected $signature = 'build';
protected $description = 'Build and optimize your Laravel app based on blaze.json';

public function handle()
{
$this->info("🚀 Starting Laravel build process...");
(new Runner($this))->run();
$this->info("✅ Build completed successfully.");
}
}