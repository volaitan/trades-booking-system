<?php
declare(strict_types=1);

// Use Composer's autoloader when installed; otherwise a tiny PSR-4 loader so the app runs with zero dependencies.
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
}

require_once __DIR__ . '/src/helpers.php'; // also autoloaded by Composer; require_once avoids a double load

// Load .env (KEY=value lines) without overriding real environment variables.
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (getenv($key) === false) {
            putenv("$key=" . trim($value, "\"'"));
        }
    }
}

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/London');

return require __DIR__ . '/config.php';
