<?php

declare(strict_types=1);

namespace EidCloud\SiteGen\Theme;

/**
 * Built-in accessible, high-performance HTML/CSS theme engine with native Bi-Directional (LTR/RTL) support.
 */
class DefaultTheme
{
    /**
     * Generate responsive, zero-layout-bug CSS with CSS logical properties and RTL adaptation.
     */
    public function getCss(): string
    {
        return <<<'CSS'
:root {
  --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans Arabic", "Noto Sans", sans-serif;
  --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  --bg-primary: #0f172a;
  --bg-surface: #1e293b;
  --bg-surface-elevated: #334155;
  --text-primary: #f8fafc;
  --text-secondary: #94a3b8;
  --text-muted: #64748b;
  --accent: #38bdf8;
  --accent-hover: #7dd3fc;
  --accent-gradient: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
  --border-color: #334155;
  --code-bg: #090d16;
  --container-max: 1200px;
  --header-height: 4.25rem;
  --radius-sm: 0.375rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;
  --transition-fast: 0.15s ease;
}

[data-theme="light"] {
  --bg-primary: #f8fafc;
  --bg-surface: #ffffff;
  --bg-surface-elevated: #f1f5f9;
  --text-primary: #0f172a;
  --text-secondary: #475569;
  --text-muted: #94a3b8;
  --accent: #0284c7;
  --accent-hover: #0369a1;
  --accent-gradient: linear-gradient(135deg, #0284c7 0%, #6366f1 100%);
  --border-color: #e2e8f0;
  --code-bg: #1e293b;
}

*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

html {
  font-family: var(--font-sans);
  background-color: var(--bg-primary);
  color: var(--text-primary);
  line-height: 1.6;
  -webkit-text-size-adjust: 100%;
  scroll-behavior: smooth;
}

body {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Logical Properties ensure 100% bug-free bi-directional mirroring */
a {
  color: var(--accent);
  text-decoration: none;
  transition: color var(--transition-fast);
}

a:hover, a:focus-visible {
  color: var(--accent-hover);
  text-decoration: underline;
}

a:focus-visible, button:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

header.site-header {
  position: sticky;
  top: 0;
  z-index: 50;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  background-color: rgba(15, 23, 42, 0.85);
  border-bottom: 1px solid var(--border-color);
  height: var(--header-height);
}

[data-theme="light"] header.site-header {
  background-color: rgba(255, 255, 255, 0.85);
}

.header-container {
  max-width: var(--container-max);
  margin-inline: auto;
  padding-inline: 1.5rem;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 700;
  font-size: 1.25rem;
  color: var(--text-primary);
  text-decoration: none !important;
}

.brand-badge {
  background: var(--accent-gradient);
  color: #ffffff;
  font-size: 0.75rem;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  font-weight: 600;
}

nav.main-nav {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav-links {
  display: flex;
  list-style: none;
  gap: 1.25rem;
  align-items: center;
}

.nav-links a {
  color: var(--text-secondary);
  font-weight: 500;
  font-size: 0.95rem;
}

.nav-links a:hover, .nav-links a.active {
  color: var(--text-primary);
}

.lang-selector {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  padding-inline-start: 1rem;
  border-inline-start: 1px solid var(--border-color);
}

.lang-btn {
  background-color: var(--bg-surface);
  color: var(--text-secondary);
  border: 1px solid var(--border-color);
  padding: 0.25rem 0.65rem;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.lang-btn.active {
  background: var(--accent-gradient);
  color: #ffffff;
  border-color: transparent;
}

/* Layout */
.layout-container {
  flex: 1;
  max-width: var(--container-max);
  width: 100%;
  margin-inline: auto;
  padding-inline: 1.5rem;
  padding-block: 2.5rem;
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 2.5rem;
}

@media (max-width: 860px) {
  .layout-container {
    grid-template-columns: 1fr;
  }
  aside.sidebar {
    display: none;
  }
}

/* Sidebar */
aside.sidebar {
  border-inline-end: 1px solid var(--border-color);
  padding-inline-end: 1.5rem;
}

.sidebar-heading {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  margin-block-end: 0.75rem;
  font-weight: 700;
}

.sidebar-menu {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.sidebar-menu a {
  display: block;
  padding: 0.4rem 0.75rem;
  border-radius: var(--radius-sm);
  color: var(--text-secondary);
  font-size: 0.9rem;
  transition: all var(--transition-fast);
}

.sidebar-menu a:hover {
  background-color: var(--bg-surface);
  color: var(--text-primary);
  text-decoration: none;
}

.sidebar-menu a.active {
  background-color: var(--bg-surface-elevated);
  color: var(--accent);
  font-weight: 600;
}

/* Main Content Typography & Elements */
main.page-content {
  min-width: 0;
}

.page-content h1, .page-content h2, .page-content h3, .page-content h4 {
  color: var(--text-primary);
  font-weight: 700;
  line-height: 1.25;
  margin-block: 1.75rem 0.75rem;
  scroll-margin-top: calc(var(--header-height) + 1rem);
}

.page-content h1 { font-size: 2.25rem; margin-block-start: 0; }
.page-content h2 { font-size: 1.75rem; border-block-end: 1px solid var(--border-color); padding-block-end: 0.4rem; }
.page-content h3 { font-size: 1.35rem; }
.page-content h4 { font-size: 1.1rem; }

.page-content p {
  margin-block-end: 1.25rem;
  color: var(--text-secondary);
  font-size: 1.05rem;
}

.page-content ul, .page-content ol {
  margin-block-end: 1.25rem;
  padding-inline-start: 1.75rem;
  color: var(--text-secondary);
}

.page-content li {
  margin-block-end: 0.35rem;
}

.page-content blockquote {
  border-inline-start: 4px solid var(--accent);
  background-color: var(--bg-surface);
  padding: 1rem 1.25rem;
  margin-block: 1.5rem;
  border-radius: 0 var(--radius-md) var(--radius-md) 0;
  font-style: italic;
  color: var(--text-primary);
}

html[dir="rtl"] .page-content blockquote {
  border-radius: var(--radius-md) 0 0 var(--radius-md);
}

.page-content pre {
  background-color: var(--code-bg);
  border: 1px solid var(--border-color);
  padding: 1.25rem;
  border-radius: var(--radius-md);
  overflow-x: auto;
  margin-block: 1.5rem;
  direction: ltr; /* Code always renders LTR */
  text-align: left;
}

.page-content code {
  font-family: var(--font-mono);
  font-size: 0.9em;
}

.page-content :not(pre) > code {
  background-color: var(--bg-surface-elevated);
  color: var(--accent);
  padding: 0.15rem 0.4rem;
  border-radius: var(--radius-sm);
}

/* GFM Data Tables */
.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-block: 1.5rem;
  font-size: 0.95rem;
  background-color: var(--bg-surface);
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.data-table th, .data-table td {
  padding: 0.75rem 1rem;
  text-align: start; /* Automatic LTR / RTL alignment */
  border-block-end: 1px solid var(--border-color);
}

.data-table th {
  background-color: var(--bg-surface-elevated);
  font-weight: 600;
  color: var(--text-primary);
}

.data-table tr:last-child td {
  border-block-end: none;
}

/* Footer */
footer.site-footer {
  border-block-start: 1px solid var(--border-color);
  padding-block: 2rem;
  background-color: var(--bg-surface);
  color: var(--text-muted);
  font-size: 0.875rem;
  margin-block-start: auto;
}

.footer-container {
  max-width: var(--container-max);
  margin-inline: auto;
  padding-inline: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}
CSS;
    }

    /**
     * Render full HTML5 page template with accessibility landmarks and directional attributes.
     *
     * @param array{
     *   lang: string,
     *   dir: string,
     *   head: string,
     *   brandTitle: string,
     *   brandUrl: string,
     *   navLinks: array<int, array{label: string, url: string, active?: bool}>,
     *   languages: array<int, array{code: string, label: string, url: string, active: bool}>,
     *   sidebarLinks: array<int, array{label: string, url: string, active?: bool}>,
     *   contentHtml: string,
     *   footerText: string,
     *   liveReloadSnippet?: string
     * } $data
     */
    public function renderPage(array $data): string
    {
        $lang = htmlspecialchars($data['lang'], ENT_QUOTES);
        $dir = htmlspecialchars($data['dir'], ENT_QUOTES);
        $brandTitle = htmlspecialchars($data['brandTitle'], ENT_QUOTES);
        $brandUrl = htmlspecialchars($data['brandUrl'], ENT_QUOTES);
        $footerText = htmlspecialchars($data['footerText'], ENT_QUOTES);
        $liveReload = $data['liveReloadSnippet'] ?? '';

        // Nav items
        $navHtml = '';
        foreach ($data['navLinks'] ?? [] as $link) {
            $lbl = htmlspecialchars($link['label'], ENT_QUOTES);
            $href = htmlspecialchars($link['url'], ENT_QUOTES);
            $cls = !empty($link['active']) ? ' class="active"' : '';
            $navHtml .= "          <li><a href=\"{$href}\"{$cls}>{$lbl}</a></li>\n";
        }

        // Language selector
        $langHtml = '';
        if (!empty($data['languages'])) {
            $langHtml .= "        <div class=\"lang-selector\" aria-label=\"Language switch\">\n";
            foreach ($data['languages'] as $l) {
                $cls = $l['active'] ? 'lang-btn active' : 'lang-btn';
                $lCode = htmlspecialchars($l['code'], ENT_QUOTES);
                $lLabel = htmlspecialchars($l['label'], ENT_QUOTES);
                $lUrl = htmlspecialchars($l['url'], ENT_QUOTES);
                $langHtml .= "          <a href=\"{$lUrl}\" class=\"{$cls}\" title=\"{$lLabel}\">{$lCode}</a>\n";
            }
            $langHtml .= "        </div>\n";
        }

        // Sidebar
        $sidebarHtml = '';
        if (!empty($data['sidebarLinks'])) {
            $sidebarHtml .= "      <aside class=\"sidebar\" aria-label=\"Documentation Sidebar\">\n";
            $sidebarHtml .= "        <p class=\"sidebar-heading\">Pages</p>\n";
            $sidebarHtml .= "        <ul class=\"sidebar-menu\">\n";
            foreach ($data['sidebarLinks'] as $slink) {
                $lbl = htmlspecialchars($slink['label'], ENT_QUOTES);
                $href = htmlspecialchars($slink['url'], ENT_QUOTES);
                $cls = !empty($slink['active']) ? ' class="active" aria-current="page"' : '';
                $sidebarHtml .= "          <li><a href=\"{$href}\"{$cls}>{$lbl}</a></li>\n";
            }
            $sidebarHtml .= "        </ul>\n";
            $sidebarHtml .= "      </aside>\n";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}" dir="{$dir}">
<head>
    {$data['head']}
    <style>
{$this->getCss()}
    </style>
</head>
<body>
    <!-- Skip to main content landmark for WCAG 2.1 AA -->
    <a href="#main-content" class="sr-only" style="position:absolute;top:-999px;left:-999px;">Skip to main content</a>

    <header class="site-header" role="banner">
      <div class="header-container">
        <a href="{$brandUrl}" class="brand-link">
          <span>{$brandTitle}</span>
          <span class="brand-badge">EidCloud</span>
        </a>
        <nav class="main-nav" role="navigation" aria-label="Main Navigation">
          <ul class="nav-links">
{$navHtml}          </ul>
{$langHtml}        </nav>
      </div>
    </header>

    <div class="layout-container">
{$sidebarHtml}      <main id="main-content" class="page-content" role="main">
{$data['contentHtml']}
      </main>
    </div>

    <footer class="site-footer" role="contentinfo">
      <div class="footer-container">
        <p>{$footerText}</p>
        <p>Built with <strong>eidcloud-site-gen</strong> • Pure PHP & Native RTL</p>
      </div>
    </footer>
{$liveReload}
</body>
</html>
HTML;
    }
}
