<?php

declare(strict_types=1);

/**
 * EidCloud SiteGen PSR-4 Class Loader (Zero vendor dependency)
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'EidCloud\\SiteGen\\';
    $baseDir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
