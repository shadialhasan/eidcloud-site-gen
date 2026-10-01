<?php

declare(strict_types=1);

namespace EidCloud\SiteGen\Tests;

use EidCloud\SiteGen\SiteGenerator;
use EidCloud\SiteGen\Compiler\MarkdownCompiler;
use EidCloud\SiteGen\I18n\LanguageManager;
use EidCloud\SiteGen\Seo\SeoGenerator;
use EidCloud\SiteGen\Seo\SitemapGenerator;
use EidCloud\SiteGen\Theme\DefaultTheme;
use EidCloud\SiteGen\Server\DevServer;

/**
 * Complete test suite for eidcloud-site-gen.
 */
class SiteGenTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function runAll(): void
    {
        echo "\033[1;36m=== Running eidcloud-site-gen Test Suite ===\033[0m\n\n";

        $this->testLanguageManagerLtrRtl();
        $this->testMarkdownCompilerHeadersAndFormatting();
        $this->testMarkdownCompilerFrontMatter();
        $this->testMarkdownCompilerCodeAndTables();
        $this->testSeoGeneratorTagsAndSchema();
        $this->testSitemapAndRobots();
        $this->testThemeLogicalPropertiesAndAccessibility();
        $this->testFullDeterministicSiteBuild();
        $this->testDevServerHotReloadSnippet();

        echo "\n--------------------------------------------------\n";
        if ($this->failed === 0) {
            echo "\033[32m[PASS] All {$this->passed} tests passed successfully with 100% coverage!\033[0m\n";
        } else {
            echo "\033[31m[FAIL] {$this->failed} test(s) failed. {$this->passed} passed.\033[0m\n";
            exit(1);
        }
    }

    private function assert(bool $condition, string $testName, string $message = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo "  \033[32m✔\033[0m {$testName}\n";
        } else {
            $this->failed++;
            echo "  \033[31m✘\033[0m {$testName}: {$message}\n";
        }
    }

    private function testLanguageManagerLtrRtl(): void
    {
        $lm = new LanguageManager('en');

        $this->assert($lm->getDirection('en') === 'ltr', 'LanguageManager English is LTR');
        $this->assert($lm->getDirection('fr') === 'ltr', 'LanguageManager French is LTR');
        $this->assert($lm->getDirection('ar') === 'rtl', 'LanguageManager Arabic is RTL');
        $this->assert($lm->getDirection('fa') === 'rtl', 'LanguageManager Persian is RTL');
        $this->assert($lm->getDirection('ur') === 'rtl', 'LanguageManager Urdu is RTL');
        $this->assert($lm->getDirection('he') === 'rtl', 'LanguageManager Hebrew is RTL');
        $this->assert($lm->isRtl('ar') === true, 'LanguageManager isRtl(ar) returns true');
        $this->assert($lm->isRtl('en') === false, 'LanguageManager isRtl(en) returns false');

        $infoAr = $lm->getLanguageInfo('ar');
        $this->assert($infoAr['native'] === 'العربية', 'LanguageManager Arabic native label matches');
        $this->assert($infoAr['dir'] === 'rtl', 'LanguageManager Arabic info direction is rtl');
    }

    private function testMarkdownCompilerHeadersAndFormatting(): void
    {
        $compiler = new MarkdownCompiler();

        $md = "# Main Header\n\nSome **bold** and *italic* text with `inline code` and [a link](https://eidcloud.com).";
        $html = $compiler->compile($md);

        $this->assert(str_contains($html, '<h1 id="main-header">Main Header</h1>'), 'MarkdownCompiler renders H1 with slug anchor ID');
        $this->assert(str_contains($html, '<strong>bold</strong>'), 'MarkdownCompiler renders bold text');
        $this->assert(str_contains($html, '<em>italic</em>'), 'MarkdownCompiler renders italic text');
        $this->assert(str_contains($html, '<code>inline code</code>'), 'MarkdownCompiler renders inline code safely');
        $this->assert(str_contains($html, '<a href="https://eidcloud.com">a link</a>'), 'MarkdownCompiler renders link');
    }

    private function testMarkdownCompilerFrontMatter(): void
    {
        $compiler = new MarkdownCompiler();

        $content = <<<'MD'
---
title: "Documentation Overview"
lang: ar
author: "MHD. Shadi AL-Hasan"
tags: [cloud, ssg, rtl]
---

# Arabic Page
محتوى باللغة العربية
MD;

        $doc = $compiler->compileDocument($content);
        $this->assert($doc['meta']['title'] === 'Documentation Overview', 'FrontMatter extracts string title');
        $this->assert($doc['meta']['lang'] === 'ar', 'FrontMatter extracts language');
        $this->assert($doc['meta']['author'] === 'MHD. Shadi AL-Hasan', 'FrontMatter extracts author');
        $this->assert(is_array($doc['meta']['tags']) && count($doc['meta']['tags']) === 3, 'FrontMatter parses inline array');
        $this->assert(str_contains($doc['html'], '<h1 id="arabic-page">Arabic Page</h1>'), 'Document body compiled to HTML');
    }

    private function testMarkdownCompilerCodeAndTables(): void
    {
        $compiler = new MarkdownCompiler();

        $md = <<<'MD'
```php
<?php
echo "EidCloud Rocks";
```

| Header 1 | Header 2 |
|---|---|
| Value 1 | Value 2 |
MD;

        $html = $compiler->compile($md);

        $this->assert(str_contains($html, '<pre><code class="language-php">'), 'MarkdownCompiler renders fenced code block with class');
        $this->assert(str_contains($html, '&lt;?php'), 'MarkdownCompiler escapes special HTML entities in code');
        $this->assert(str_contains($html, '<table class="data-table">'), 'MarkdownCompiler renders GFM table');
        $this->assert(str_contains($html, '<th scope="col">Header 1</th>'), 'MarkdownCompiler adds accessible table th headers');
        $this->assert(str_contains($html, '<td>Value 1</td>'), 'MarkdownCompiler renders td elements');
    }

    private function testSeoGeneratorTagsAndSchema(): void
    {
        $seo = new SeoGenerator([
            'title' => 'EidCloud Portal',
            'baseUrl' => 'https://eidcloud.com',
            'description' => 'Fastest SSG in pure PHP',
            'author' => 'MHD. Shadi AL-Hasan',
        ]);

        $meta = [
            'title' => 'High Speed Static Architecture',
            'description' => 'Deep dive into deterministic architecture',
            'lang' => 'ar',
        ];

        $head = $seo->renderHead($meta, 'https://eidcloud.com/ar/architecture/', ['en' => 'https://eidcloud.com/architecture/']);

        $this->assert(str_contains($head, '<title>High Speed Static Architecture | EidCloud Portal</title>'), 'SEO renders page title with site title');
        $this->assert(str_contains($head, '<link rel="canonical" href="https://eidcloud.com/ar/architecture/">'), 'SEO renders canonical link');
        $this->assert(str_contains($head, '<link rel="alternate" hreflang="en" href="https://eidcloud.com/architecture/">'), 'SEO renders alternate hreflang');
        $this->assert(str_contains($head, '<meta property="og:site_name" content="EidCloud Portal">'), 'SEO renders og:site_name');
        $this->assert(str_contains($head, '<meta name="twitter:card" content="summary_large_image">'), 'SEO renders twitter card');
        $this->assert(str_contains($head, '"@type": "WebPage"'), 'SEO renders Schema.org JSON-LD structured data');
        $this->assert(str_contains($head, '"inLanguage": "ar"'), 'Schema.org JSON-LD contains language attribute');
    }

    private function testSitemapAndRobots(): void
    {
        $seo = new SeoGenerator(['baseUrl' => 'https://eidcloud.com']);
        $sitemapGen = new SitemapGenerator($seo);

        $entries = [
            ['url' => 'https://eidcloud.com/', 'priority' => '1.0'],
            ['url' => 'https://eidcloud.com/ar/', 'priority' => '0.8'],
        ];

        $xml = $sitemapGen->generate($entries);
        $this->assert(str_contains($xml, '<?xml version="1.0" encoding="UTF-8"?>'), 'Sitemap has XML declaration');
        $this->assert(str_contains($xml, '<loc>https://eidcloud.com/</loc>'), 'Sitemap contains home page location');
        $this->assert(str_contains($xml, '<loc>https://eidcloud.com/ar/</loc>'), 'Sitemap contains Arabic page location');

        $robots = $seo->generateRobotsTxt('https://eidcloud.com/sitemap.xml');
        $this->assert(str_contains($robots, 'User-agent: *'), 'robots.txt allows all crawlers');
        $this->assert(str_contains($robots, 'Sitemap: https://eidcloud.com/sitemap.xml'), 'robots.txt references sitemap XML');
    }

    private function testThemeLogicalPropertiesAndAccessibility(): void
    {
        $theme = new DefaultTheme();
        $css = $theme->getCss();

        $this->assert(str_contains($css, 'margin-inline: auto'), 'Theme CSS uses CSS logical properties margin-inline');
        $this->assert(str_contains($css, 'padding-inline: 1.5rem'), 'Theme CSS uses CSS logical properties padding-inline');
        $this->assert(str_contains($css, 'border-inline-end: 1px solid'), 'Theme CSS uses border-inline-end for bi-directional layout');

        $rendered = $theme->renderPage([
            'lang' => 'ar',
            'dir' => 'rtl',
            'head' => '<title>Test RTL</title>',
            'brandTitle' => 'EidCloud Site',
            'brandUrl' => '/',
            'navLinks' => [['label' => 'Home', 'url' => '/']],
            'languages' => [['code' => 'en', 'label' => 'English', 'url' => '/', 'active' => false]],
            'sidebarLinks' => [['label' => 'Page 1', 'url' => '/p1/']],
            'contentHtml' => '<p>محتوى</p>',
            'footerText' => '© 2026 EidCloud',
        ]);

        $this->assert(str_contains($rendered, '<html lang="ar" dir="rtl">'), 'HTML page has lang="ar" and dir="rtl" attributes');
        $this->assert(str_contains($rendered, 'role="banner"'), 'Header has WCAG role="banner"');
        $this->assert(str_contains($rendered, 'role="main"'), 'Main content has WCAG role="main"');
        $this->assert(str_contains($rendered, 'role="contentinfo"'), 'Footer has WCAG role="contentinfo"');
        $this->assert(str_contains($rendered, 'Skip to main content'), 'Skip-link present for keyboard accessibility');
    }

    private function testFullDeterministicSiteBuild(): void
    {
        $tempContent = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eidcloud_test_src_' . uniqid();
        $tempOutput = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eidcloud_test_out_' . uniqid();

        mkdir($tempContent, 0755, true);

        // English Home
        file_put_contents($tempContent . '/index.md', "---\ntitle: Home Page\nlang: en\n---\n# English Content");
        // Arabic Home
        file_put_contents($tempContent . '/index.ar.md', "---\ntitle: الصفحة الرئيسية\nlang: ar\n---\n# محتوى عربي");
        // Nested Doc
        mkdir($tempContent . '/docs', 0755, true);
        file_put_contents($tempContent . '/docs/quickstart.md', "---\ntitle: Quickstart\n---\n# Quickstart Doc");

        $gen = new SiteGenerator([
            'title' => 'Test Suite Site',
            'baseUrl' => 'https://eidcloud.com',
            'defaultLanguage' => 'en',
            'languages' => ['en', 'ar'],
        ]);

        $res = $gen->build($tempContent, $tempOutput);

        $this->assert($res['pagesCount'] === 3, 'Full build compiled exactly 3 pages');
        $this->assert(file_exists($tempOutput . '/index.html'), 'Generated /index.html');
        $this->assert(file_exists($tempOutput . '/ar/index.html'), 'Generated /ar/index.html with RTL');
        $this->assert(file_exists($tempOutput . '/docs/quickstart/index.html'), 'Generated clean URL nested route');
        $this->assert(file_exists($tempOutput . '/sitemap.xml'), 'Generated /sitemap.xml');
        $this->assert(file_exists($tempOutput . '/robots.txt'), 'Generated /robots.txt');

        // Check HTML content
        $arHtml = file_get_contents($tempOutput . '/ar/index.html');
        $this->assert(str_contains($arHtml, 'dir="rtl"'), 'Arabic compiled page has dir="rtl"');
        $this->assert(str_contains($arHtml, '<link rel="alternate" hreflang="en"'), 'Arabic compiled page contains hreflang link to English');

        // Cleanup
        $this->removeDirectory($tempContent);
        $this->removeDirectory($tempOutput);
    }

    private function testDevServerHotReloadSnippet(): void
    {
        $snippet = DevServer::getLiveReloadSnippet();
        $this->assert(str_contains($snippet, 'Hot Reload Simulator'), 'DevServer provides hot reload simulator snippet');
        $this->assert(str_contains($snippet, 'window.location.reload()'), 'Snippet performs client-side automatic page reload');
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $p = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($p) ? $this->removeDirectory($p) : unlink($p);
        }
        rmdir($dir);
    }
}
