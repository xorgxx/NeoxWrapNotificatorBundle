<?php

declare(strict_types=1);

// Robust bootstrap for running the bundle tests from the project root.
// It loads the application's Composer autoloader (../../../../vendor/autoload.php).

$autoloadPaths = [
    __DIR__ . '/../../../vendor/autoload.php',        // project root vendor when run from project root
    __DIR__ . '/../../vendor/autoload.php',           // fallback: if the bundle is used as a dependency and tests run from bundle root
    __DIR__ . '/vendor/autoload.php',                 // fallback: if a local vendor exists inside tests (unlikely)
];

$loaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    fwrite(STDERR, "Could not locate Composer autoload.php. Tried:\n - " . implode("\n - ", $autoloadPaths) . "\n");
    exit(1);
}
