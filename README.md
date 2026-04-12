# Church Website Starter

This repository is a WordPress starter for a church website built around:

- WordPress on PHP 8.2
- MySQL 8
- A custom theme at `wp-content/themes/church-theme`
- A custom plugin at `wp-content/plugins/church-core`

The initial scope matches the approved MVP:

- `Home`
- `About`
- `Sermons`
- `Contact`

## What Is Included

- Docker-based local WordPress + MySQL environment
- `church-theme` with custom templates for home, sermons, pages, and contact
- `church-core` plugin with:
  - `sermon` custom post type
  - `speaker` taxonomy
  - sermon metadata fields
  - sermon archive filtering
  - built-in contact form shortcode and message storage
- Bootstrap script to install WordPress locally, activate the theme/plugin, and create the launch pages

## Local Setup

1. Copy `.env.example` to `.env`.
2. Adjust the admin credentials and site title if needed.
3. Start and bootstrap the site:

```bash
cp .env.example .env
./bin/bootstrap-wordpress.sh
```

After setup:

- Site: `http://localhost:8080`
- Admin: `http://localhost:8080/wp-admin/`

## Working Structure

- `docker-compose.yml`: local WordPress/MySQL stack
- `bin/bootstrap-wordpress.sh`: installs WordPress and seeds the initial content structure
- `wp-content/themes/church-theme`: custom PHP theme
- `wp-content/plugins/church-core`: sermon model and contact workflow

WordPress core is not committed. The Docker image supplies it, and the repo only tracks the custom code.

## Content Model

### Pages

- `Home` is set as the static front page.
- `About` and `Contact` are standard pages.
- `Sermons` is the archive for the `sermon` custom post type.

### Sermons

Each sermon supports:

- title
- preacher via `speaker` taxonomy
- sermon date
- Scripture reference
- embedded YouTube URL
- audio URL
- rich text summary notes using the main editor

## Contact Form

The theme renders a built-in shortcode: `[church_contact_form]`

Behavior:

- stores submissions in WordPress under `Contact Messages`
- sends an email using `wp_mail()`
- protects against CSRF with a nonce
- includes a honeypot field

For production on Hostinger, install and configure an SMTP plugin so `wp_mail()` reliably reaches the church inbox.

## Deployment To Hostinger

Use Hostinger managed WordPress for the live site, then deploy only the custom code:

1. Upload `church-theme` as a theme.
2. Upload `church-core` as a plugin.
3. Activate both in WordPress admin.
4. Recreate the pages or use WP-CLI if available.
5. Configure:
   - site title and tagline
   - homepage content in the Customizer
   - contact email, phone, address, and map URL
   - SMTP plugin
   - SEO plugin

Recommended production plugins:

- SMTP plugin for mail delivery
- SEO plugin such as Yoast SEO or Rank Math
- Hostinger-provided caching/optimization plugin only

## Validation Commands

Basic config check:

```bash
docker compose config
```

Open a shell in the WP-CLI container:

```bash
docker compose run --rm wpcli shell
```

## Next Content Tasks

- replace placeholder homepage and about copy
- add the church logo and final brand colors
- update service times and address in the Customizer
- add real sermons with YouTube and audio links
- configure the final contact inbox and SMTP delivery
