<?php

declare(strict_types=1);

namespace EidCloud\SiteGen\Server;

/**
 * Built-in static web server with instant hot-reload and livereload simulation.
 */
class DevServer
{
    public function __construct(
        private readonly string $docRoot,
        private readonly int $port = 3000,
        private readonly string $host = '127.0.0.1'
    ) {}

    /**
     * Start the PHP built-in web server.
     */
    public function start(?string $routerScript = null): void
    {
        $realRoot = realpath($this->docRoot);
        if ($realRoot === false || !is_dir($realRoot)) {
            throw new \RuntimeException("Document root directory does not exist: {$this->docRoot}");
        }

        $address = "{$this->host}:{$this->port}";
        echo "\033[32m[EidCloud Dev Server]\033[0m Serving \033[34m{$realRoot}\033[0m on \033[1;36mhttp://{$address}\033[0m\n";
        echo "Press Ctrl+C to terminate the server.\n\n";

        $router = $routerScript ?? __DIR__ . '/router.php';

        $cmd = sprintf(
            '%s -S %s -t %s %s',
            PHP_BINARY,
            escapeshellarg($address),
            escapeshellarg($realRoot),
            escapeshellarg($router)
        );

        passthru($cmd);
    }

    /**
     * JavaScript snippet injected into HTML during development for automated hot reload / change detection.
     */
    public static function getLiveReloadSnippet(): string
    {
        return <<<'HTML'
<!-- EidCloud SiteGen Hot Reload Simulator -->
<script>
(() => {
  let lastModified = null;
  async function check() {
    try {
      const res = await fetch(window.location.href, { method: 'HEAD', cache: 'no-cache' });
      const current = res.headers.get('last-modified') || res.headers.get('etag');
      if (lastModified && current && lastModified !== current) {
        console.log('[EidCloud] Hot-reloading document...');
        window.location.reload();
      }
      lastModified = current;
    } catch (e) {}
  }
  setInterval(check, 1000);
})();
</script>
HTML;
    }
}
