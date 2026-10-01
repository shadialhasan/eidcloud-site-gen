<?php

declare(strict_types=1);

namespace EidCloud\SiteGen;

use EidCloud\SiteGen\Compiler\MarkdownCompiler;
use EidCloud\SiteGen\I18n\LanguageManager;
use EidCloud\SiteGen\Seo\SeoGenerator;
use EidCloud\SiteGen\Theme\DefaultTheme;
use EidCloud\SiteGen\Server\DevServer;

/**
 * Main Static Site Generator orchestrator.
 */
class SiteGenerator
{
    private MarkdownCompiler $markdownCompiler;
    private LanguageManager $languageManager;
    private SeoGenerator $seoGenerator;
    private DefaultTheme $theme;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'title' => 'EidCloud Site',
            'baseUrl' => 'https://eidcloud.com',
            'description' => 'High-Speed Static Site Built with EidCloud SiteGen',
            'author' => 'MHD. Shadi AL-Hasan',
            'defaultLanguage' => 'en',
            'languages' => ['en', 'ar'],
            'footerText' => '© ' . date('Y') . ' MHD. Shadi AL-Hasan. All rights reserved.',
        ];

        $this->config = array_merge($defaultConfig, $config);
        $this->markdownCompiler = new MarkdownCompiler();
        $this->languageManager = new LanguageManager((string)$this->config['defaultLanguage']);
        $this->seoGenerator = new SeoGenerator($this->config);
        $this->theme = new DefaultTheme();
    }

    /**
     * Build site from source content directory into destination directory.
     *
     * @return array{pagesCount: int, durationMs: float, outputDir: string}
     */
    public function build(string $contentDir, string $outputDir, bool $devMode = false): array
    {
        $startTime = microtime(true);

        if (!is_dir($contentDir)) {
            throw new \InvalidArgumentException("Source content directory does not exist: {$contentDir}");
        }

        // Load optional site manifest if present (site.json or site.yaml)
        $manifestPathJson = rtrim($contentDir, '/\\') . DIRECTORY_SEPARATOR . 'site.json';
        if (file_exists($manifestPathJson)) {
            $json = json_decode(file_get_contents($manifestPathJson) ?: '', true);
            if (is_array($json)) {
                $this->config = array_merge($this->config, $json);
                $this->seoGenerator = new SeoGenerator($this->config);
            }
        }

        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
            throw new \RuntimeException("Unable to create output directory: {$outputDir}");
        }

        // Discover all markdown documents
        $files = $this->scanDirectory($contentDir);
        $pages = [];
        $routes = []; // Map of [routeId => [lang => pageData]]

        foreach ($files as $filePath) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['md', 'markdown'])) {
                // Copy static assets
                $this->copyAsset($filePath, $contentDir, $outputDir);
                continue;
            }

            $rawContent = file_get_contents($filePath);
            if ($rawContent === false) continue;

            $doc = $this->markdownCompiler->compileDocument($rawContent);
            $relPath = ltrim(substr($filePath, strlen($contentDir)), '/\\');

            // Detect language & clean route
            $pageInfo = $this->resolvePageMetadata($relPath, $doc['meta']);
            $pageData = [
                'filePath' => $filePath,
                'relPath' => $relPath,
                'route' => $pageInfo['route'],
                'lang' => $pageInfo['lang'],
                'dir' => $pageInfo['dir'],
                'meta' => array_merge($doc['meta'], [
                    'title' => $pageInfo['title'],
                    'lang' => $pageInfo['lang'],
                    'dir' => $pageInfo['dir'],
                ]),
                'html' => $doc['html'],
            ];

            $pages[] = $pageData;
            $routes[$pageInfo['routeId']][$pageInfo['lang']] = $pageData;
        }

        // Build navigation & sidebar items
        $baseUrl = rtrim((string)$this->config['baseUrl'], '/');
        $sitemapEntries = [];

        foreach ($pages as $page) {
            $routeId = $this->extractRouteId($page['route'], $page['lang']);
            $alternates = [];

            if (isset($routes[$routeId])) {
                foreach ($routes[$routeId] as $altLang => $altPage) {
                    $alternates[$altLang] = $this->buildUrl($baseUrl, $altPage['route']);
                }
            }

            $canonicalUrl = $this->buildUrl($baseUrl, $page['route']);
            $sitemapEntries[] = [
                'url' => $canonicalUrl,
                'lastmod' => date('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => ($page['route'] === '' || $page['route'] === '/' || $page['route'] === 'index.html') ? '1.0' : '0.8',
            ];

            // Build alternate languages toggle for theme
            $languagesToggle = [];
            foreach ($this->config['languages'] as $langCode) {
                $langInfo = $this->languageManager->getLanguageInfo($langCode);
                $targetUrl = isset($routes[$routeId][$langCode])
                    ? $this->buildRelativeUrl($routes[$routeId][$langCode]['route'])
                    : ($langCode === $this->config['defaultLanguage'] ? '/' : "/{$langCode}/");

                $languagesToggle[] = [
                    'code' => $langCode,
                    'label' => $langInfo['native'],
                    'url' => $targetUrl,
                    'active' => ($langCode === $page['lang']),
                ];
            }

            // Build sidebar & nav links
            $sidebarLinks = [];
            foreach ($pages as $p) {
                if ($p['lang'] === $page['lang']) {
                    $sidebarLinks[] = [
                        'label' => $p['meta']['title'],
                        'url' => $this->buildRelativeUrl($p['route']),
                        'active' => ($p['route'] === $page['route']),
                    ];
                }
            }

            $headHtml = $this->seoGenerator->renderHead($page['meta'], $canonicalUrl, $alternates);

            $pageRenderData = [
                'lang' => $page['lang'],
                'dir' => $page['dir'],
                'head' => $headHtml,
                'brandTitle' => (string)($this->config['title'] ?? 'EidCloud Site'),
                'brandUrl' => $page['lang'] === $this->config['defaultLanguage'] ? '/' : "/{$page['lang']}/",
                'navLinks' => [
                    ['label' => ($page['lang'] === 'ar' ? 'الرئيسية' : 'Home'), 'url' => ($page['lang'] === $this->config['defaultLanguage'] ? '/' : "/{$page['lang']}/")],
                    ['label' => ($page['lang'] === 'ar' ? 'التوثيق' : 'Docs'), 'url' => '#'],
                ],
                'languages' => $languagesToggle,
                'sidebarLinks' => $sidebarLinks,
                'contentHtml' => $page['html'],
                'footerText' => (string)($this->config['footerText'] ?? ''),
                'liveReloadSnippet' => $devMode ? DevServer::getLiveReloadSnippet() : '',
            ];

            $renderedHtml = $this->theme->renderPage($pageRenderData);

            // Determine output file path
            $targetHtmlPath = $this->resolveOutputHtmlPath($outputDir, $page['route']);
            $targetDir = dirname($targetHtmlPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            file_put_contents($targetHtmlPath, $renderedHtml);
        }

        // Generate automated XML sitemap & robots.txt
        $sitemapXml = $this->seoGenerator->generateSitemap($sitemapEntries);
        file_put_contents(rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . 'sitemap.xml', $sitemapXml);

        $robotsTxt = $this->seoGenerator->generateRobotsTxt("{$baseUrl}/sitemap.xml");
        file_put_contents(rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . 'robots.txt', $robotsTxt);

        $durationMs = (microtime(true) - $startTime) * 1000;

        return [
            'pagesCount' => count($pages),
            'durationMs' => round($durationMs, 2),
            'outputDir' => $outputDir,
        ];
    }

    /**
     * @return array<string>
     */
    private function scanDirectory(string $dir): array
    {
        $files = [];
        $items = scandir($dir);
        if ($items === false) return [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $files = array_merge($files, $this->scanDirectory($fullPath));
            } else {
                $files[] = $fullPath;
            }
        }
        return $files;
    }

    private function copyAsset(string $src, string $contentDir, string $outputDir): void
    {
        $relPath = ltrim(substr($src, strlen($contentDir)), '/\\');
        $destPath = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . $relPath;
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        copy($src, $destPath);
    }

    /**
     * @param array<string, mixed> $meta
     * @return array{title: string, lang: string, dir: string, route: string, routeId: string}
     */
    private function resolvePageMetadata(string $relPath, array $meta): array
    {
        $normalized = str_replace('\\', '/', $relPath);
        $pathInfo = pathinfo($normalized);
        $filename = $pathInfo['filename'];
        $dir = $pathInfo['dirname'] === '.' ? '' : $pathInfo['dirname'];

        // Determine language:
        // 1. From FrontMatter 'lang'
        // 2. From directory prefix (e.g. ar/guide.md)
        // 3. From suffix (e.g. guide.ar.md)
        $lang = (string)($meta['lang'] ?? '');
        $cleanName = $filename;

        if (preg_match('/\.([a-z]{2})$/i', $filename, $m)) {
            $cleanName = substr($filename, 0, -3);
            if ($lang === '') {
                $lang = strtolower($m[1]);
            }
        }

        if ($lang === '') {
            if (preg_match('/^([a-z]{2})\//i', $dir . '/', $m)) {
                $lang = strtolower($m[1]);
            } else {
                $lang = (string)$this->config['defaultLanguage'];
            }
        }

        $dirAttr = $this->languageManager->getDirection($lang);
        $title = (string)($meta['title'] ?? ucfirst(str_replace(['-', '_'], ' ', $cleanName)));

        // Compute route
        // Default language root: index.html or <name>/index.html
        // Multilingual: <lang>/index.html or <lang>/<name>/index.html
        $routeSegments = [];
        if ($lang !== $this->config['defaultLanguage']) {
            $routeSegments[] = $lang;
        }

        $subDirs = array_filter(explode('/', $dir), fn($s) => $s !== '' && $s !== $lang);
        foreach ($subDirs as $sd) {
            $routeSegments[] = $sd;
        }

        if ($cleanName !== 'index') {
            $routeSegments[] = $cleanName;
        }

        $route = empty($routeSegments) ? 'index.html' : implode('/', $routeSegments) . '/index.html';
        $routeId = empty($subDirs) && $cleanName === 'index' ? 'home' : (empty($subDirs) ? $cleanName : implode('/', $subDirs) . '/' . $cleanName);

        return [
            'title' => $title,
            'lang' => $lang,
            'dir' => $dirAttr,
            'route' => $route,
            'routeId' => $routeId,
        ];
    }

    private function extractRouteId(string $route, string $lang): string
    {
        $clean = preg_replace('#/index\.html$#', '', $route);
        $clean = preg_replace('#^' . preg_quote($lang, '#') . '/?#', '', $clean);
        return $clean === '' ? 'home' : $clean;
    }

    private function buildUrl(string $baseUrl, string $route): string
    {
        if ($route === 'index.html') {
            return $baseUrl . '/';
        }
        $clean = preg_replace('#/index\.html$#', '/', $route);
        return $baseUrl . '/' . ltrim($clean, '/');
    }

    private function buildRelativeUrl(string $route): string
    {
        if ($route === 'index.html') {
            return '/';
        }
        $clean = preg_replace('#/index\.html$#', '/', $route);
        return '/' . ltrim($clean, '/');
    }

    private function resolveOutputHtmlPath(string $outputDir, string $route): string
    {
        $normalized = str_replace('/', DIRECTORY_SEPARATOR, $route);
        return rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getMarkdownCompiler(): MarkdownCompiler
    {
        return $this->markdownCompiler;
    }

    public function getLanguageManager(): LanguageManager
    {
        return $this->languageManager;
    }

    public function getSeoGenerator(): SeoGenerator
    {
        return $this->seoGenerator;
    }
}
