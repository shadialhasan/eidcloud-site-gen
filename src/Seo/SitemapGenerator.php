<?php

declare(strict_types=1);

namespace EidCloud\SiteGen\Seo;

/**
 * Convenience wrapper for XML Sitemap generation.
 */
class SitemapGenerator
{
    public function __construct(
        private readonly SeoGenerator $seoGenerator
    ) {}

    /**
     * @param array<int, array{url: string, lastmod?: string, changefreq?: string, priority?: string}> $entries
     */
    public function generate(array $entries): string
    {
        return $this->seoGenerator->generateSitemap($entries);
    }
}
