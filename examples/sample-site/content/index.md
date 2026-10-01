---
title: "Welcome to EidCloud"
description: "Ultra-fast deterministic static site generator in pure PHP"
lang: en
---

# Welcome to EidCloud SiteGen

**eidcloud-site-gen** is an ultra-fast, zero-vendor-dependency static site generator built specifically for lightning documentation and multilingual websites with full Bi-Directional (LTR & RTL) support.

## Key Features

- **Blazing Fast**: Compiles entire documentation trees in under 50ms.
- **Bi-Directional Support**: Native RTL for Arabic, Hebrew, Urdu, and Persian.
- **Automated SEO**: OpenGraph, Twitter Cards, XML Sitemaps, and Schema.org JSON-LD.
- **Semantic HTML**: Fully accessible WCAG 2.1 AA compliant markup.

```bash
# Compile site instantly
php bin/eidcloud-site build ./content/ --out=./dist/
```

| Metric | EidCloud SiteGen | Typical Generator |
|---|---|---|
| Dependencies | 0 (Pure PHP) | 50+ packages |
| Build Time | < 50ms | 1.5s - 5s |
| RTL Support | First-Class Built-in | Plugin-dependent |

> "Speed and determinism make documentation sites effortlessly maintainable."
