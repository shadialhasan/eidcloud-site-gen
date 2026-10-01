<?php

declare(strict_types=1);

namespace EidCloud\SiteGen\Compiler;

/**
 * Fast, pure PHP Markdown compiler with FrontMatter extraction.
 * No external dependencies required.
 */
class MarkdownCompiler
{
    /**
     * Parse raw document text containing optional FrontMatter (YAML or JSON) and Markdown body.
     *
     * @return array{meta: array<string, mixed>, markdown: string, html: string}
     */
    public function compileDocument(string $rawContent): array
    {
        $parsed = $this->extractFrontMatter($rawContent);
        $html = $this->compile($parsed['markdown']);

        return [
            'meta' => $parsed['meta'],
            'markdown' => $parsed['markdown'],
            'html' => $html,
        ];
    }

    /**
     * Extract FrontMatter (--- ... --- or ;;; ... ;;;) and return metadata array + pure markdown.
     *
     * @return array{meta: array<string, mixed>, markdown: string}
     */
    public function extractFrontMatter(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $meta = [];
        $markdown = $raw;

        if (preg_match('/^---\n(.*?)\n---\n*(.*)$/s', $raw, $matches)) {
            $meta = $this->parseYamlLike($matches[1]);
            $markdown = $matches[2];
        } elseif (preg_match('/^;;;\n(.*?)\n;;;\n*(.*)$/s', $raw, $matches)) {
            $jsonParsed = json_decode($matches[1], true);
            if (is_array($jsonParsed)) {
                $meta = $jsonParsed;
            }
            $markdown = $matches[2];
        }

        return [
            'meta' => $meta,
            'markdown' => $markdown,
        ];
    }

    /**
     * Lightweight YAML-subset parser for FrontMatter key-value pairs, lists, and quotes.
     *
     * @return array<string, mixed>
     */
    public function parseYamlLike(string $yaml): array
    {
        $lines = explode("\n", $yaml);
        $data = [];
        $currentKey = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // Check for list items under a key
            if (str_starts_with($trimmed, '- ') && $currentKey !== null) {
                $item = trim(substr($trimmed, 2));
                $data[$currentKey][] = $this->castValue($item);
                continue;
            }

            // Key-value pair
            if (str_contains($line, ':')) {
                $parts = explode(':', $line, 2);
                $key = trim($parts[0]);
                $val = trim($parts[1]);

                if ($val === '') {
                    $currentKey = $key;
                    $data[$key] = [];
                } else {
                    $currentKey = null;
                    $data[$key] = $this->castValue($val);
                }
            }
        }

        return $data;
    }

    private function castValue(string $val): mixed
    {
        $val = trim($val);
        // Quoted strings
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            return substr($val, 1, -1);
        }

        $lower = strtolower($val);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;

        // In-line array [a, b, c]
        if (str_starts_with($val, '[') && str_ends_with($val, ']')) {
            $inner = trim(substr($val, 1, -1));
            if ($inner === '') return [];
            return array_map(fn($item) => $this->castValue(trim($item)), explode(',', $inner));
        }

        if (is_numeric($val)) {
            return str_contains($val, '.') ? (float)$val : (int)$val;
        }

        return $val;
    }

    /**
     * Compile Markdown into semantic, accessible HTML5.
     */
    public function compile(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);

        // Protect code blocks first
        $codeBlocks = [];
        $markdown = preg_replace_callback('/```([a-zA-Z0-9_\-\.]*)\n(.*?)\n```/s', function ($m) use (&$codeBlocks) {
            $idx = count($codeBlocks);
            $lang = trim($m[1]);
            $code = htmlspecialchars($m[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $class = $lang !== '' ? ' class="language-' . htmlspecialchars($lang, ENT_QUOTES) . '"' : '';
            $codeBlocks[$idx] = "<pre><code{$class}>{$code}</code></pre>";
            return "%%CODEBLOCK_{$idx}%%";
        }, $markdown);

        // Protect inline code
        $inlineCodes = [];
        $markdown = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$inlineCodes) {
            $idx = count($inlineCodes);
            $inlineCodes[$idx] = '<code>' . htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>';
            return "%%INLINECODE_{$idx}%%";
        }, $markdown);

        // Headers with automated accessible IDs for anchor linking
        $markdown = preg_replace_callback('/^(#{1,6})\s+(.+)$/m', function ($m) {
            $level = strlen($m[1]);
            $title = trim($m[2]);
            $slug = $this->slugify($title);
            return "<h{$level} id=\"{$slug}\">{$title}</h{$level}>";
        }, $markdown);

        // Blockquotes
        $markdown = preg_replace_callback('/^(?:>\s*(.+)\n?)+/m', function ($m) {
            $lines = explode("\n", trim($m[0]));
            $cleaned = [];
            foreach ($lines as $line) {
                $cleaned[] = preg_replace('/^>\s?/', '', $line);
            }
            $body = implode("<br>\n", $cleaned);
            return "<blockquote>\n<p>{$body}</p>\n</blockquote>";
        }, $markdown);

        // Unordered lists
        $markdown = preg_replace_callback('/(?:^[ \t]*[\*\-\+]\s+[^\n]+\n?)+/m', function ($m) {
            $lines = explode("\n", trim($m[0]));
            $items = '';
            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                $itemText = preg_replace('/^[ \t]*[\*\-\+]\s+/', '', $line);
                $items .= "  <li>{$itemText}</li>\n";
            }
            return "<ul>\n{$items}</ul>";
        }, $markdown);

        // Ordered lists
        $markdown = preg_replace_callback('/(?:^[ \t]*\d+\.\s+[^\n]+\n?)+/m', function ($m) {
            $lines = explode("\n", trim($m[0]));
            $items = '';
            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                $itemText = preg_replace('/^[ \t]*\d+\.\s+/', '', $line);
                $items .= "  <li>{$itemText}</li>\n";
            }
            return "<ol>\n{$items}</ol>";
        }, $markdown);

        // Horizontal rules
        $markdown = preg_replace('/^[ \t]*(\*{3,}|-{3,}|_{3,})[ \t]*$/m', '<hr>', $markdown);

        // Tables (GitHub Flavored Markdown)
        $markdown = preg_replace_callback('/(?:^\|.+\|\n?)+/m', function ($m) {
            $lines = array_filter(explode("\n", trim($m[0])));
            if (count($lines) < 2) return $m[0];

            $headerLine = array_shift($lines);
            $sepLine = array_shift($lines);

            if (!str_contains($sepLine, '-')) {
                return $m[0];
            }

            $headers = array_map('trim', explode('|', trim($headerLine, '|')));
            $thHtml = '';
            foreach ($headers as $h) {
                $thHtml .= "    <th scope=\"col\">{$h}</th>\n";
            }

            $tbHtml = '';
            foreach ($lines as $row) {
                $cells = array_map('trim', explode('|', trim($row, '|')));
                $tbHtml .= "  <tr>\n";
                foreach ($cells as $cell) {
                    $tbHtml .= "    <td>{$cell}</td>\n";
                }
                $tbHtml .= "  </tr>\n";
            }

            return "<table class=\"data-table\">\n<thead>\n  <tr>\n{$thHtml}  </tr>\n</thead>\n<tbody>\n{$tbHtml}</tbody>\n</table>";
        }, $markdown);

        // Images: ![alt](url "title")
        $markdown = preg_replace('/!\[([^\]]*)\]\(([^" \)]+)(?:\s+"([^"]*)")?\)/', '<img src="$2" alt="$1"$3 ? title="$3" : "" loading="lazy">', $markdown);

        // Links: [title](url)
        $markdown = preg_replace('/\[([^\]]+)\]\(([^ \)]+)\)/', '<a href="$2">$1</a>', $markdown);

        // Bold and Italic
        $markdown = preg_replace('/\*\*\*([^\*]+)\*\*\*/', '<strong><em>$1</em></strong>', $markdown);
        $markdown = preg_replace('/___([^_]+)___/', '<strong><em>$1</em></strong>', $markdown);
        $markdown = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $markdown);
        $markdown = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $markdown);
        $markdown = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $markdown);
        $markdown = preg_replace('/_([^_]+)_/', '<em>$1</em>', $markdown);
        $markdown = preg_replace('/~~([^~]+)~~/', '<del>$1</del>', $markdown);

        // Paragraphs: split by double newlines, ignoring already block-level tags
        $blocks = preg_split('/\n{2,}/', trim($markdown));
        $result = [];

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') continue;

            // If it starts with block element, keep as is
            if (preg_match('/^<(?:h[1-6]|ul|ol|table|blockquote|pre|hr|div|section|p)/i', $block) ||
                str_starts_with($block, '%%CODEBLOCK_')) {
                $result[] = $block;
            } else {
                // Line breaks inside paragraph
                $pContent = nl2br($block);
                $result[] = "<p>{$pContent}</p>";
            }
        }

        $html = implode("\n\n", $result);

        // Restore inline code
        foreach ($inlineCodes as $idx => $codeHtml) {
            $html = str_replace("%%INLINECODE_{$idx}%%", $codeHtml, $html);
        }

        // Restore code blocks
        foreach ($codeBlocks as $idx => $codeBlockHtml) {
            $html = str_replace("%%CODEBLOCK_{$idx}%%", $codeBlockHtml, $html);
        }

        return $html;
    }

    /**
     * Create an accessible URL-friendly slug.
     */
    public function slugify(string $text): string
    {
        $text = strip_tags($text);
        // Support Latin + Arabic/non-Latin scripts by cleaning control characters
        $slug = preg_replace('~[^\pL\d\s\-_]+~u', '', $text);
        $slug = preg_replace('~[\s\-_]+~u', '-', $slug);
        $slug = trim($slug, '-');
        $slug = mb_strtolower($slug, 'UTF-8');

        return $slug === '' ? 'heading' : $slug;
    }
}
