<?php

set_time_limit(300);

// User function for asking yes/no questions
function askYesNo(string $question, bool $default = false): bool
{
    $answer = trim(fgets(STDIN));
    return $answer === '' ? $default : strtolower($answer) === 'yes' || strtolower($answer) === 'y';
}

// Ask for migration inclusion
echo "Do you want to include migration files? (yes/no): ";
$includeMigration = askYesNo("");

// Ask for full cache build
echo "Do you want to enable full cache? (yes/no): ";
$includeCache = askYesNo("");

// If migration is not included, ask for other file inclusions
$includeResources = false;
$includeRoute = false;

if (!$includeCache) {
    echo "Do you want to include the 'resources' folder? (yes/no): ";
    $includeResources = askYesNo("");

    echo "Do you want to include the 'routes' folder? (yes/no): ";
    $includeRoute = askYesNo("");
}

// Ask for specific device build
echo "Do you want to build for a specific device? (yes/no): ";
$includeSpecificDevice = askYesNo("");





// Load configuration from JSON file
if (!file_exists(__DIR__ . '/blaze.json')) {
    throw new Exception('Configuration file not found.');
}

// Read and decode the JSON configuration file
$jsonContent = file_get_contents(__DIR__ . '/blaze.json');
if ($jsonContent === false) {
    throw new Exception('Failed to read configuration file.');
}
$config = json_decode($jsonContent, true);



// Define build directories
$buildDir = __DIR__ . '/' . $config['build']['output_directory'] ?? 'build';
$coreDir = $buildDir . '/core';
$publicDir = $buildDir . '/public';
$sourceDirs = $config['source']['directories'];
$files = $config['source']['files'];



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
function runCommand($cmd, $cwd = null)
{
    echo "▶️  $cmd\n";

    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $proc = proc_open($cmd, $descriptorSpec, $pipes, $cwd . '/core');

    if (is_resource($proc)) {
        while (!feof($pipes[1])) {
            echo fread($pipes[1], 4096);
        }
        fclose($pipes[1]);

        while (!feof($pipes[2])) {
            echo fread($pipes[2], 4096);
        }
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

// Function to delete a directory and its contents
function deleteDir($dir)
{
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        $file->isDir() ? rmdir($file) : unlink($file);
    }
    rmdir($dir);
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


function encryptPhpFileDeviceWise($filePath, $key)
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
function encryptAppFolderOnly($appPath, $key, $specificDevice = false)
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($appPath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS)
    );
    foreach ($iterator as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && is_file($file)) {
            if ($specificDevice) {
                encryptPhpFileDeviceWise($file, $key);
            } else {
                encryptPhpFile($file, $key);
            }

            echo "🔐 " . str_replace($appPath . DIRECTORY_SEPARATOR, '', $file) . "\n";
        }
    }
}

// Step-by-step build process
// STEP 1: Clean
cleanBuild($buildDir);



// STEP 3: Copy Laravel core files (using parallelization for faster processing)
echo "🔄 Syncing core files...\n";
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
echo "🚀 Preparing root files...\n";
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

    # Ensure everything is routed through the public directory
    RewriteCond %{REQUEST_URI} !^/core/public/
    RewriteRule ^(.*)$ core/public/$1 [L]

    # Force everything through index.php for non-existent files or directories
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]

    # Redirect WWW to non-WWW (Optional, remove if not required)
    RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
    RewriteRule ^ http://%1%{REQUEST_URI} [L,R=301]

    # Force HTTPS (Optional, remove if not required)
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Prevent directory listing (Security)
    Options -Indexes

    # Set expiration for static content (Improve caching)
    <FilesMatch "\.(jpg|jpeg|png|gif|css|js|ico|pdf|txt|html)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
    </FilesMatch>

    # Leverage browser caching for static content (Speed up page loading)
    <IfModule mod_headers.c>
        <FilesMatch "\.(jpg|jpeg|png|gif|css|js|ico|pdf|txt|html)$">
            Header set Cache-Control "max-age=31536000, public"
        </FilesMatch>
    </IfModule>

    # Block access to sensitive files (Security)
    <FilesMatch "(^\.|wp-config.php|\.htaccess|\.git)">
        Order Deny,Allow
        Deny from all
    </FilesMatch>

</IfModule>



HTACCESS;
file_put_contents("$buildDir/.htaccess", $htaccess, LOCK_EX);
$totalFiles++;
echo "✅ .htaccess created.\n";
//runCommand("composer dump-autoload --optimize", $buildDir);

// STEP 8: Laravel optimize (optimized for faster execution)
echo "🧹 Optimizing Laravel...\n";
runCommand("composer install --prefer-dist --no-dev --optimize-autoloader --no-progress --no-interaction", $buildDir);


echo "🧹 Clearing the application cache \n";
runCommand("php artisan optimize:clear", $buildDir); // ✅ Clears config, route, view, event caches

if ($includeCache) {
    runCommand("php artisan config:cache", $buildDir);
    runCommand("php artisan route:cache", $buildDir);
    runCommand("php artisan view:cache", $buildDir);
    runCommand("php artisan event:cache", $buildDir);
    runCommand("php artisan optimize", $buildDir);
    foreach (['resources', 'routes'] as $folder) {
        $path =  $buildDir . "/core/$folder";

        if (is_dir($path)) {
            echo "🗑️ Removing $folder...\n";
            deleteDir($path);
        }
    }
}
if (!$includeCache && !$includeResources) {
    runCommand("php artisan view:cache", $buildDir);
    runCommand("php artisan event:cache", $buildDir);
    foreach (['resources'] as $folder) {
        $path =  $buildDir . "/core/$folder";
        if (is_dir($path)) {
            echo "🗑️ Removing $folder...\n";
            deleteDir($path);
        }
    }
}
if (!$includeCache && !$includeRoute) {
    runCommand("php artisan route:cache", $buildDir);
    foreach (['routes'] as $folder) {
        $path =  $buildDir . "/core/$folder";
        if (is_dir($path)) {
            echo "🗑️ Removing $folder...\n";
            deleteDir($path);
        }
    }
}
if (!$includeMigration) {
    // Remove migration files if not included
    $migrationPath = $buildDir . "/core/database/migrations";
    if (is_dir($migrationPath)) {
        echo "🗑️ Removing migrations...\n";
        deleteDir($migrationPath);
    }
}








// STEP 9: Encrypt app folder only
echo "🔐 Encrypting app folder...\n";
encryptAppFolderOnly("$coreDir/app", hash('sha256', $baseKey), $includeSpecificDevice);

// FINAL REPORT
echo "\n🎉 Build Complete!\n";
echo "📦 Total Files Processed: $totalFiles\n";
echo "📁 Build Output: $buildDir\n";
