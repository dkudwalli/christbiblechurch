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

Page templates follow WordPress naming conventions (`front-page.php`, `page-about-us.php`, `single-sermon.php`, `archive-sermon.php`, `taxonomy-series.php`, etc.). Reusable template pieces live in `template-parts/` (`sermon-card.php`, `event-card.php`, `page-section.php`, `section-nav.php`).

Theme settings use WordPress Customizer via `get_theme_mod()` / `church_theme_get_mod()` (defined in `functions.php`). All configurable strings (hero copy, service times, contact details, Instagram credentials) come from Customizer mods with defaults defined in `church_theme_defaults()`.

CSS is split into `assets/css/site.css`, `forms.css`, and `accessibility.css`. No build step — plain CSS.

### Custom Plugin: `wp-content/plugins/church-core/`

Bootstrapped in `church-core.php`; each subsystem is a `final class` with a static `boot()` method called from `Church_Core::boot()`:

| Class | Responsibility |
|---|---|
| `Church_Core_Sermons` | `sermon` CPT, `series` / `speaker` taxonomies, sermon meta fields |
| `Church_Core_Sermon_Import` | Creates WP posts from normalized sermon data |
| `Church_Core_Sermon_Sync_Service` | Orchestrates YouTube → sermon sync logic |
| `Church_Core_Sermon_Cron` | WP-Cron schedule for weekly auto-sync |
| `Church_Core_Sermon_Sync_Admin` | Admin UI under `Sermons > YouTube Sync` |
| `Church_Core_Youtube_Client` | YouTube Data API v3 calls (channels, playlistItems, videos) |
| `Church_Core_Contact` | `[church_contact_form]` shortcode, CSRF nonce, honeypot, `wp_mail()` |
| `Church_Core_Events` | `event` CPT |
| `Church_Core_Page_Sections` | Per-page section layout meta (`church_section_layout`, `church_section_profiles`) stored as post meta on child pages of About Us and Worship |

### Page Section System

`About Us` and `Worship` pages render their child pages as named anchor sections. Each child page can have a layout set via the "Section Presentation" meta box:
- `default` — content card
- `feature` — image + content (uses featured image)
- `elder_board` — profile cards from `church_section_profiles` meta

`Church_Core_Page_Sections` handles schema migrations via `SCHEMA_VERSION` / `church_core_section_schema_version` option.

### Route Shims

Committed PHP redirect shims at `sermons/`, `series/`, `speaker/`, `contact/`, `about/` delegate to the WordPress root `index.php`. These provide a file-based fallback when Hostinger/Apache routing doesn't pass pretty URLs into WordPress.

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

### Deployment Notes

Production is Hostinger managed WordPress. Deploy only `wp-content/themes/church-theme/` and `wp-content/plugins/church-core/`. After deploying, flush permalinks (`Settings > Permalinks > Save Changes`). Keep `wp-config.php` only on the server — this repo provides `wp-config.example.php` as a template.
