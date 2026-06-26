# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress-based church website for Crossroad South Church (South Bangalore). Runs on PHP 8.2 / MySQL 8 / WordPress 6.5.5 in Docker locally; deployed to Hostinger managed WordPress in production. Only custom code is committed — WordPress core, bundled plugins, and `wp-content/uploads` are excluded from Git.

## Common Commands

**Start local environment:**
```bash
cp .env.example .env
./bin/bootstrap-wordpress.sh
```
Site: `http://localhost:8080` | Admin: `http://localhost:8080/wp-admin/`

**Lint (PHP syntax + PHPCS + CSS):**
```bash
npm run lint          # all three checks
npm run lint:php      # php -l syntax check only
npm run lint:phpcs    # PSR-12 via phpcs.phar (auto-downloads if missing, falls back to Docker)
npm run lint:css      # stylelint on theme CSS
```

**Smoke tests (requires running local site):**
```bash
npm run test:smoke    # Playwright against http://localhost:8080
PLAYWRIGHT_BASE_URL=http://localhost:8080 npx playwright test tests/playwright/smoke.spec.js
```

**WP-CLI:**
```bash
docker compose run --rm wpcli <wp-cli-command>
# e.g.: docker compose run --rm wpcli post list --post_type=sermon
```

**Docker:**
```bash
docker compose up -d db wordpress   # start services
docker compose config               # validate compose file
```

## Architecture

### Custom Theme: `wp-content/themes/church-theme/`

Page templates follow WordPress naming conventions (`front-page.php`, `page-about-us.php`, `page-worship.php`, `page-gallery.php`, `page-give.php`, `single-sermon.php`, `archive-sermon.php`, `single-event.php`, `archive-event.php`, `taxonomy-series.php`, etc.). Reusable template pieces live in `template-parts/` (`sermon-card.php`, `event-card.php`, `page-section.php`, `page-sections-body.php`, `section-nav.php`).

Theme settings use WordPress Customizer via `get_theme_mod()` / `church_theme_get_mod()` (defined in `functions.php`). All configurable strings (hero copy, service times, contact details, Instagram credentials) come from Customizer mods with defaults defined in `church_theme_defaults()`.

CSS is split into `assets/css/site.css`, `forms.css`, and `accessibility.css`. No build step — plain CSS.

### Custom Plugin: `wp-content/plugins/church-core/`

`church-core.php` requires every class, then `Church_Core::boot()` calls the static `boot()` on the top-level subsystems (`Sermons`, `Sermon_Import`, `Sermon_Cron`, `Sermon_Sync_Admin`, `Events`, `Contact`, `Page_Sections`). The remaining classes are stateless helpers/services invoked by those subsystems — they have no `boot()` and are called statically.

| Class | Booted | Responsibility |
|---|---|---|
| `Church_Core_Sermons` | ✓ | `sermon` CPT, `series` / `speaker` taxonomies, sermon meta fields |
| `Church_Core_Sermon_Import` | ✓ | Creates WP posts from normalized sermon data |
| `Church_Core_Sermon_Cron` | ✓ | WP-Cron schedule for weekly auto-sync |
| `Church_Core_Sermon_Sync_Admin` | ✓ | Admin UI under `Sermons > YouTube Sync` |
| `Church_Core_Contact` | ✓ | `[church_contact_form]` shortcode, CSRF nonce, honeypot, `wp_mail()` |
| `Church_Core_Events` | ✓ | `event` CPT |
| `Church_Core_Page_Sections` | ✓ | Per-page section layout meta (`church_section_layout`, `church_section_profiles`) stored as post meta on child pages of About Us and Worship |
| `Church_Core_Sermon_Sync_Service` | | Orchestrates YouTube → sermon sync logic |
| `Church_Core_Youtube_Client` | | YouTube Data API v3 calls (channels, playlistItems, videos) |
| `Church_Core_Scripture_Extractor` | | `from_title()` — parses a scripture reference (e.g. `Mark 8:22-26`) out of a YouTube video title; maps book aliases |
| `Church_Core_Term_Helper` | | `ensure_term()` — find-or-create a `series`/`speaker` term during sync/import |

### Page Section System

`About Us` and `Worship` pages render their child pages as named anchor sections. Each child page can have a layout set via the "Section Presentation" meta box:
- `default` — content card
- `feature` — image + content (uses featured image)
- `elder_board` — profile cards from `church_section_profiles` meta

`Church_Core_Page_Sections` handles schema migrations via `SCHEMA_VERSION` / `church_core_section_schema_version` option.

### Route Shims

A file-based fallback for when Hostinger/Apache routing doesn't pass pretty URLs into WordPress. Committed route directories sit at the repo root, each holding an `index.php` shim:

- **Page/post routes** (`about/`, `about-us/`, `contact/`, `contact-us/`, `events/`, `gallery/`, `give/`, `worship/`, `sermons/`, `series/`, `speaker/`, and individual sermon subdirs like `sermons/<slug>/`) simply `require` the WordPress root `index.php`, letting WP resolve the URL normally.
- **Taxonomy term routes** (`series/<term>/`, `speaker/<term>/`) instead `require` the root `taxonomy-route-shim.php` and call `church_route_shim_boot_taxonomy($taxonomy, $slug)`, which forces the taxonomy/term query vars and boots WordPress directly.
- **Photo album routes** (`photo-albums/<slug>/`) are not committed snapshots. `Church_Core_Photo_Albums` creates and removes those shim directories automatically at runtime so Hostinger fallback routing stays aligned with published albums.

The page/post and taxonomy shim directories are committed snapshots of published URLs — when adding a sermon, series, or speaker, add the matching shim directory so the file-based fallback stays in sync. Photo album shims are the exception and should be managed by the plugin instead of by hand.

### Content Model

Sermon post meta keys: `sermon_date`, `scripture_reference`, `youtube_url`, `youtube_video_id`, `audio_url`.  
Taxonomies: `series` (hierarchical), `speaker` (non-hierarchical).

### YouTube Sync Flow

1. Channel ID → resolve uploads playlist ID (channels endpoint)
2. Playlist → recent video IDs (playlistItems endpoint)
3. Video IDs → full details (videos endpoint)
4. Deduplication via `youtube_video_id` post meta before creating sermon posts
5. Default sync: Sundays at 12:30 PM Asia/Kolkata via WP-Cron

### Code Standards

- PHP follows PSR-12 (excluding namespace requirement and a few WordPress-style exceptions — see `phpcs.xml.dist`)
- All PHP files guard with `if (! defined('ABSPATH')) { exit; }` at the top
- CSS linted via `stylelint-config-standard`
- Smoke tests use Playwright + axe-core for accessibility checks on public pages
- CI (`.github/workflows/frontend-quality.yml`) runs on every push/PR: `npm run lint`, then bootstraps WordPress and runs `npm run test:smoke`. Run `npm run lint` locally before pushing to match the gate.

### Deployment Notes

Production is Hostinger managed WordPress. Deploy only `wp-content/themes/church-theme/` and `wp-content/plugins/church-core/`. After deploying, flush permalinks (`Settings > Permalinks > Save Changes`). Keep `wp-config.php` only on the server — this repo provides `wp-config.example.php` as a template.
