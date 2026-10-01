<?php

declare(strict_types=1);

namespace EidCloud\SiteGen\Seo;

/**
 * Generates automated SEO meta tags, OpenGraph, Twitter Cards, Schema.org JSON-LD,
 * XML Sitemaps, and robots.txt.
 */
class SeoGenerator
{
    /**
     * @param array<string, mixed> $siteConfig
     */
    public function __construct(
        private readonly array $siteConfig
    ) {}

    /**
     * Build rich HTML head metadata (Meta tags, Open Graph, Twitter cards, Canonical, Alternate languages, JSON-LD).
     *
     * @param array<string, mixed> $pageMeta
     * @param array<string, string> $alternates [lang => url]
     */
    public function renderHead(array $pageMeta, string $canonicalUrl, array $alternates = []): string
    {
        $siteTitle = (string)($this->siteConfig['title'] ?? 'EidCloud Site');
        $siteUrl = rtrim((string)($this->siteConfig['baseUrl'] ?? ''), '/');
        $pageTitle = (string)($pageMeta['title'] ?? $siteTitle);
        $fullTitle = ($pageTitle !== $siteTitle) ? "{$pageTitle} | {$siteTitle}" : $siteTitle;
        $description = (string)($pageMeta['description'] ?? $this->siteConfig['description'] ?? '');
        $author = (string)($pageMeta['author'] ?? $this->siteConfig['author'] ?? 'MHD. Shadi AL-Hasan');
        $ogImage = (string)($pageMeta['image'] ?? $this->siteConfig['defaultImage'] ?? ($siteUrl . '/assets/og-image.png'));
        $lang = (string)($pageMeta['lang'] ?? 'en');
        $datePublished = (string)($pageMeta['date'] ?? date('c'));

        $lines = [];
        $lines[] = '<meta charset="UTF-8">';
        $lines[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $lines[] = '<title>' . htmlspecialchars($fullTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>';
        $lines[] = '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">';
        $lines[] = '<meta name="author" content="' . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . '">';
        $lines[] = '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">';

        // Alternate language tags for i18n SEO
        foreach ($alternates as $altLang => $altUrl) {
            $lines[] = '<link rel="alternate" hreflang="' . htmlspecialchars($altLang, ENT_QUOTES) . '" href="' . htmlspecialchars($altUrl, ENT_QUOTES) . '">';
        }
        if (!empty($alternates)) {
            $defaultLang = (string)($this->siteConfig['defaultLanguage'] ?? 'en');
            $xDefault = $alternates[$defaultLang] ?? $canonicalUrl;
            $lines[] = '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($xDefault, ENT_QUOTES) . '">';
        }

        // OpenGraph
        $lines[] = '<!-- OpenGraph Metadata -->';
        $lines[] = '<meta property="og:site_name" content="' . htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') . '">';
        $lines[] = '<meta property="og:title" content="' . htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') . '">';
        $lines[] = '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">';
        $lines[] = '<meta property="og:url" content="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">';
        $lines[] = '<meta property="og:type" content="website">';
        $lines[] = '<meta property="og:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">';
        $lines[] = '<meta property="og:locale" content="' . htmlspecialchars(str_replace('-', '_', $lang), ENT_QUOTES) . '">';

        // Twitter Card
        $lines[] = '<!-- Twitter Card -->';
        $lines[] = '<meta name="twitter:card" content="summary_large_image">';
        $lines[] = '<meta name="twitter:title" content="' . htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') . '">';
        $lines[] = '<meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">';
        $lines[] = '<meta name="twitter:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">';

        // Schema.org JSON-LD structured data
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $pageTitle,
            'headline' => $pageTitle,
            'description' => $description,
            'url' => $canonicalUrl,
            'inLanguage' => $lang,
            'datePublished' => $datePublished,
            'author' => [
                '@type' => 'Person',
                'name' => $author,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteTitle,
                'url' => $siteUrl,
            ],
        ];

        $lines[] = '<!-- Schema.org JSON-LD Structured Data -->';
        $lines[] = '<script type="application/ld+json">';
        $lines[] = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $lines[] = '</script>';

        return implode("\n    ", $lines);
    }

    /**
     * Generate standard XML sitemap for indexed pages.
     *
     * @param array<int, array{url: string, lastmod?: string, changefreq?: string, priority?: string}> $entries
     */
    public function generateSitemap(array $entries): string
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($entries as $entry) {
            $url = htmlspecialchars($entry['url'], ENT_XML1, 'UTF-8');
            $lastmod = htmlspecialchars($entry['lastmod'] ?? date('Y-m-d'), ENT_XML1, 'UTF-8');
            $changefreq = htmlspecialchars($entry['changefreq'] ?? 'weekly', ENT_XML1, 'UTF-8');
            $priority = htmlspecialchars($entry['priority'] ?? '0.8', ENT_XML1, 'UTF-8');

            $xml[] = '  <url>';
            $xml[] = "    <loc>{$url}</loc>";
            $xml[] = "    <lastmod>{$lastmod}</lastmod>";
            $xml[] = "    <changefreq>{$changefreq}</changefreq>";
            $xml[] = "    <priority>{$priority}</priority>";
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml) . "\n";
    }

    /**
     * Generate robots.txt with sitemap reference.
     */
    public function generateRobotsTxt(?string $sitemapUrl = null): string
    {
        $siteUrl = rtrim((string)($this->siteConfig['baseUrl'] ?? ''), '/');
        $sitemapUrl = $sitemapUrl ?? ($siteUrl !== '' ? "{$siteUrl}/sitemap.xml" : '/sitemap.xml');

        return "User-agent: *\nAllow: /\n\nSitemap: {$sitemapUrl}\n";
    }
}
