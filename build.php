<?php

set_time_limit(300);

$buildDir = __DIR__ . '/build';
$coreDir = $buildDir . '/core';
$publicDir = $buildDir . '/public';
$sourceDirs = ['app', 'bootstrap', 'config', 'database', 'public', 'routes', 'storage','resources', 'vendor'];
$files = ['artisan', 'composer.json', 'composer.lock', 'package.json', 'vite.config.js', 'phpunit.xml'];

// Function to load environment variables from the .env file
function loadEnv($envFile = '.env')
{
    if (!file_exists($envFile)) {
        throw new Exception('Environment file not found.');
    }

    $envVariables = [];
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $envVariables[trim($key)] = trim($value);
        }
    }

    if (isset($envVariables['APP_KEY'])) {
        return $envVariables['APP_KEY'];
    }

    throw new Exception('APP_KEY not found in .env file.');
}

$baseKey = loadEnv(); // Get the APP_KEY from the .env file

// Function to run shell commands
function runCommand($cmd)
{
    echo "▶️  $cmd\n";
    $proc = proc_open($cmd, [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);

    if (is_resource($proc)) {
        while (!feof($pipes[1]))
            echo fread($pipes[1], 4096);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
    }
}

// Function to clean the build directory
function cleanBuild($dir)
{
    echo "🧨  Cleaning old build folder...\n";
    if (is_dir($dir)) {
        exec("rd /s /q \"$dir\"", $out, $code);
        if ($code !== 0) {
            sleep(1);
            exec("rd /s /q \"$dir\"");
        }
    }
    mkdir($dir, 0755, true);
    echo "🧹 Build folder ready.\n";
}

// Function to copy files with robocopy for faster transfer
function robocopyCopy($src, $dst)
{
    $cmd = "robocopy \"$src\" \"$dst\" /MIR /MT:8 /NFL /NDL /NJH /NJS /NC /NS /NP";
    exec($cmd, $output);
    return count($output);
}

// Function to encrypt PHP files with AES-256-CBC run specific  machine
// This version uses machineID to ensure the encrypted code runs only on the same machine

/*
function encryptPhpFile($filePath, $key)
{
    $originalCode = file_get_contents($filePath);
    if ($originalCode === false)
        return;

    $iv = random_bytes(16);
    $machineID = php_uname('n');
    $finalKey = hash('sha256', $key . $machineID, true);
    $encrypted = openssl_encrypt($originalCode, 'AES-256-CBC', $finalKey, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false)
        return;

    $payload = base64_encode($iv . $encrypted);
    $payload = strtr($payload, '+/=', '-_~');

    $loader = <<<PHP
<?php
if (PHP_SAPI !== 'cli-server') header('Content-Type: text/html; charset=utf-8');
\$b = strtr(substr(file_get_contents(__FILE__), __COMPILER_HALT_OFFSET__), '-_~', '+/=');
\$d = base64_decode(\$b);
\$iv = substr(\$d, 0, 16);
\$data = substr(\$d, 16);
\$key = hash('sha256', '$key' . php_uname('n'), true);
eval("?>".openssl_decrypt(\$data, 'AES-256-CBC', \$key, OPENSSL_RAW_DATA, \$iv));
__halt_compiler();
$payload
PHP;

    file_put_contents($filePath, $loader, LOCK_EX);
}
*/



// Function to encrypt PHP files with AES-256-CBC it run on any machine
// This version does not use machineID, so it can run on any machine

function encryptPhpFile($filePath, $key)
{
    $originalCode = file_get_contents($filePath);
    if ($originalCode === false) return;

    $iv = random_bytes(16);
    $finalKey = hash('sha256', $key, true); // removed machineID
    $encrypted = openssl_encrypt($originalCode, 'AES-256-CBC', $finalKey, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) return;

    $payload = base64_encode($iv . $encrypted);
    $payload = strtr($payload, '+/=', '-_~');

    // Obfuscated loader
    $loader = <<<PHP
<?php
if(PHP_SAPI!=='cli-server')@header('Content-Type:text/html;charset=utf-8');
\$x1="strtr";\$x2="substr";\$x3="file_get_contents";\$x4="base64_decode";\$x5="openssl_decrypt";\$x6="hash";\$x7="sha256";
\$p=\$x1(\$x2(\$x3(__FILE__),__COMPILER_HALT_OFFSET__),"-_~","+/=");
\$p=\$x4(\$p);
\$v=\$x2(\$p,0,16);
\$c=\$x2(\$p,16);
\$k=\$x6(\$x7,'$key',true);
@eval("?>".\$x5(\$c,"AES-256-CBC",\$k,OPENSSL_RAW_DATA,\$v));
__halt_compiler();
$payload
PHP;

    file_put_contents($filePath, $loader, LOCK_EX);
}

// Encrypt the app folder
function encryptAppFolderOnly($appPath, $key)
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($appPath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS)
    );
    foreach ($iterator as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && is_file($file)) {
            encryptPhpFile($file, $key);
            echo "🔐 " . str_replace($appPath . DIRECTORY_SEPARATOR, '', $file) . "\n";
        }
    }
}

// Step-by-step build process
// STEP 1: Clean
cleanBuild($buildDir);

// STEP 2: Laravel optimize (optimized for faster execution)
echo "🧹 Optimizing Laravel...\n";
// runCommand("composer install --prefer-dist --no-dev --no-scripts --optimize-autoloader --classmap-authoritative --no-progress --no-interaction");
// runCommand("php artisan config:cache");
// runCommand("php artisan route:cache");
// runCommand("php artisan view:cache");
// runCommand("php artisan event:cache");
// runCommand("php artisan optimize");
runCommand("composer dump-autoload --optimize");
//  runCommand("composer install  --optimize-autoloader");



// STEP 3: Copy Laravel core files (using parallelization for faster processing)
echo "📁 Copying Laravel core...\n";
$totalFiles = 0;
foreach ($sourceDirs as $dir) {
    $src = __DIR__ . "/$dir";
    $dst = "$coreDir/$dir";
    if (is_dir($src)) {
        if (!is_dir($dst))
            mkdir($dst, 0755, true);
        $count = robocopyCopy($src, $dst);
        $totalFiles += $count;
        echo "✅ [$count] $dir\n";
    }
}

// STEP 4: Copy root files
echo "📄 Copying root files...\n";
foreach ($files as $file) {
    $src = __DIR__ . "/$file";
    $dst = "$coreDir/$file";
    if (file_exists($src)) {
        copy($src, $dst);
        $totalFiles++;
        echo "✅ $file\n";
    }
}

// // STEP 5: Copy public assets
// echo "🌐 Copying public folder...\n";
// if (!is_dir($publicDir)) mkdir($publicDir, 0755, true);
// $totalFiles += robocopyCopy(__DIR__ . '/public', $publicDir);

// STEP 6: Create index.php loader
echo "⚙️  Creating index.php...\n";
// Read Laravel version from composer.json
$composerFile = file_get_contents(__DIR__ . '/composer.json');
$composerData = json_decode($composerFile, true);

// Look for 'laravel/framework' in the 'require' section
$laravelVersion = isset($composerData['require']['laravel/framework']) ? $composerData['require']['laravel/framework'] : 'Unknown';

// Output the Laravel version
echo "Laravel Version: $laravelVersion\n";
// Compare the Laravel version with '11.0.0'
if ($laravelVersion !== 'Unknown' && version_compare($laravelVersion, '11.0.0', '>=')) {
    $indexPhp = <<<PHP
    <?php
    
    
    use Illuminate\Http\Request;
    define('LARAVEL_START', microtime(true));
    
    
    \$corePath = __DIR__ . '/core';
    
    if (file_exists(\$maintenance = \$corePath . '/storage/framework/maintenance.php')) {
        require \$maintenance;
    }
    
    require \$corePath . '/vendor/autoload.php';
    \$app = require_once \$corePath . '/bootstrap/app.php';
    \$app->handleRequest(Request::capture());
    PHP;
} else {
    $indexPhp = <<<PHP
    <?php
    
    use Illuminate\\Http\\Request;
    use Illuminate\\Contracts\\Http\\Kernel;
    
    define('LARAVEL_START', microtime(true));
    
    \$corePath = __DIR__ . '/core';
    
    if (file_exists(\$maintenance = \$corePath . '/storage/framework/maintenance.php')) {
        require \$maintenance;
    }
    
    require \$corePath . '/vendor/autoload.php';
    \$app = require_once \$corePath . '/bootstrap/app.php';
    
    \$kernel = \$app->make(Kernel::class);
    \$response = \$kernel->handle(\$request = Request::capture())->send();
    \$kernel->terminate(\$request, \$response);
    PHP;

}



file_put_contents("$buildDir/index.php", $indexPhp, LOCK_EX);
$totalFiles++;
echo "✅ index.php created.\n";

// STEP 7: Create .htaccess
echo "⚙️  Creating .htaccess...\n";
$htaccess = <<<HTACCESS
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Force everything through index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>


HTACCESS;
file_put_contents("$buildDir/.htaccess", $htaccess, LOCK_EX);
$totalFiles++;
echo "✅ .htaccess created.\n";

// STEP 8: Encrypt app folder only
echo "🔐 Encrypting app folder...\n";
encryptAppFolderOnly("$coreDir/app", hash('sha256', $baseKey));

// FINAL REPORT
echo "\n🎉 Build Complete!\n";
echo "📦 Total Files Processed: $totalFiles\n";
echo "📁 Build Output: $buildDir\n";
