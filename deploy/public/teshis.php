<?php
/**
 * kurye.tech teşhis — kurulumdan sonra SİLİN: rm public/teshis.php
 */
header('Content-Type: text/plain; charset=utf-8');
echo "=== KURYE TESHIS ===\n\n";
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'CWD: ' . getcwd() . "\n\n";

$root = dirname(__DIR__);
echo "Root: $root\n\n";

$checks = [
    'vendor/autoload.php',
    'vendor/symfony/deprecation-contracts/function.php',
    'bootstrap/app.php',
    '.env',
    'storage/logs',
    'bootstrap/cache',
    'public/build/manifest.json',
];
foreach ($checks as $f) {
    $p = $root . '/' . $f;
    echo ($f . ': ' . (file_exists($p) ? 'OK' : 'EKSIK') . "\n");
}

echo "\n--- .env ozet ---\n";
$envPath = $root . '/.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^(APP_|DB_|CACHE_|QUEUE_|SESSION_)/', $line) && !str_contains($line, 'PASSWORD')) {
            echo $line . "\n";
        }
        if (str_starts_with($line, 'DB_PASSWORD=')) {
            echo 'DB_PASSWORD=' . (strlen(trim(substr($line, 12))) > 0 ? '(dolu)' : '(BOS!)') . "\n";
        }
    }
}

echo "\n--- Laravel boot ---\n";
try {
    require $root . '/vendor/autoload.php';
    $app = require $root . '/bootstrap/app.php';
    echo "BOOT: OK\n";
} catch (Throwable $e) {
    echo "BOOT HATA:\n" . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
}
