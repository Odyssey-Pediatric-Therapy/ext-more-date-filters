<?php

require dirname(__DIR__) . '/site/vendor/autoload.php';

// Test the source tree, even when the development site's copied extension is stale.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Espo\\Modules\\MoreDateFilters\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = dirname(__DIR__) . '/src/files/custom/Espo/Modules/MoreDateFilters/' .
        str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require $file;
}, true, true);
