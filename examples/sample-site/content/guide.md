---
title: "Installation & Quickstart Guide"
description: "How to set up and run EidCloud SiteGen in seconds"
lang: en
---

# Installation & Quickstart

Get started with **eidcloud-site-gen** in seconds. Because it requires zero vendor packages, you can deploy or run it anywhere PHP 8.2+ is available.

## Quick CLI Usage

```bash
# 1. Clone repository
git clone https://github.com/eidcloud/eidcloud-site-gen.git
cd eidcloud-site-gen

# 2. Build sample documentation
php bin/eidcloud-site build ./examples/sample-site/content/ --out=./dist/

# 3. Start local server with hot-reload
php bin/eidcloud-site serve ./dist/ --port=3000
```
