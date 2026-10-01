<?php

declare(strict_types=1);

/**
 * Router script for PHP built-in web server.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);

if ($docRoot === false) {
    return false;
}

$filePath = $docRoot . $uri;

// 1. If static file directly exists, serve it
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// 2. If directory has index.html, serve that
if (is_dir($filePath)) {
    $indexFile = rtrim($filePath, '/\\') . DIRECTORY_SEPARATOR . 'index.html';
    if (file_exists($indexFile)) {
        require $indexFile;
        return true;
    }
}

// 3. Clean URLs: /about -> /about.html
if (file_exists($filePath . '.html')) {
    require $filePath . '.html';
    return true;
}

// 4. Clean URLs: /about/ -> /about/index.html
if (file_exists($filePath . '/index.html')) {
    require $filePath . '/index.html';
    return true;
}

// 404 Fallback
http_response_code(404);
echo "404 Not Found - EidCloud Dev Server";
return true;
