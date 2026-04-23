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
  - `series` taxonomy
  - `speaker` taxonomy
  - sermon metadata fields
  - YouTube-to-sermon sync tooling
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

WordPress core is not committed. The Docker image supplies it, and the repo only tracks the custom church code plus the clean-route shims under `about/`, `contact/`, `sermons/`, `series/`, and `speaker/`.

On Hostinger, this repository is meant to be pulled into an existing WordPress install. Do not add WordPress core files, bundled plugins, or `wp-content/uploads` to Git, because Hostinger's pull-based deploy will refuse to overwrite the live untracked install.

## Content Model

### Pages

- `Home` is set as the static front page.
- `About` and `Contact` are standard pages.
- `Sermons` is the archive for the `sermon` custom post type.

### Sermons

Each sermon supports:

- title
- series via `series` taxonomy
- preacher via `speaker` taxonomy
- sermon date
- Scripture reference
- embedded YouTube URL
- stored YouTube video ID for sync deduplication
- audio URL
- rich text summary notes using the main editor

### YouTube Sync

The `church-core` plugin now includes a YouTube sync workflow for sermons:

- pulls newly uploaded videos from a configured YouTube channel
- creates `sermon` posts automatically
- stores `youtube_video_id` and `youtube_url` in post meta
- extracts `scripture_reference` from YouTube titles when a recognizable Bible reference is present, including common short forms such as `Col. 3:16-17` and `Jn. 3:16`
- auto-assigns the `speaker` taxonomy term `Pastor Benji`
- backfills missing `youtube_video_id` values on existing sermons that already have a `youtube_url`

The default auto-sync schedule is:

- Sunday
- 12:30 PM
- WordPress site timezone

Important:

- Set `Settings > General > Timezone` to `Asia/Kolkata` on the church site so sermon dates and the weekly sync schedule align with Sunday service uploads.
- This implementation creates sermons after the recording/upload appears on YouTube. It does not create placeholder sermons for scheduled or currently live streams.

#### Plugin Structure

```text
wp-content/plugins/church-core/
├── church-core.php
├── assets/
│   └── admin.js
└── includes/
    ├── class-church-core.php
    ├── class-church-core-contact.php
    ├── class-church-core-events.php
    ├── class-church-core-sermon-cron.php
    ├── class-church-core-sermon-import.php
    ├── class-church-core-sermon-sync-admin.php
    ├── class-church-core-sermon-sync-service.php
    ├── class-church-core-sermons.php
    └── class-church-core-youtube-client.php
```

#### Configure the YouTube Sync

1. Go to `Settings > General` and set the site timezone to `Asia/Kolkata`.
2. In WordPress admin, open `Sermons > YouTube Sync`.
3. Save:
   - YouTube API key
   - channel ID
   - weekly sync day/time
4. Confirm the `Next scheduled sync` value appears on the settings page.
5. Use `Run Sync Now` once to verify the connection.

#### Manual Testing

1. Add the API key and channel ID on `Sermons > YouTube Sync`.
2. Click `Run Sync Now`.
3. Confirm a new sermon is created only for uploads that do not already exist in the archive.
4. Open the created sermon and verify:
   - title matches the YouTube title
   - `sermon_date` reflects the local publish date
   - `youtube_url` is stored
   - `youtube_video_id` is stored
   - `speaker` is set to `Pastor Benji`
5. Click `Run Sync Now` again and confirm no duplicate sermon is created for the same video.

#### Example YouTube API Flow

The sync uses YouTube Data API v3 in this order:

1. Resolve the uploads playlist:

```http
GET https://www.googleapis.com/youtube/v3/channels?part=contentDetails&id=CHANNEL_ID&key=API_KEY
```

Mock response:

```json
{
  "items": [
    {
      "contentDetails": {
        "relatedPlaylists": {
          "uploads": "UUXAMPLE_UPLOADS_PLAYLIST"
        }
      }
    }
  ]
}
```

2. Fetch recent uploaded videos:

```http
GET https://www.googleapis.com/youtube/v3/playlistItems?part=snippet,contentDetails&playlistId=UUXAMPLE_UPLOADS_PLAYLIST&maxResults=25&key=API_KEY
```

Mock response:

```json
{
  "items": [
    {
      "snippet": {
        "title": "Mark 1:1-8 - Prepare the Way",
        "liveBroadcastContent": "none"
      },
      "contentDetails": {
        "videoId": "abc123xyz89",
        "videoPublishedAt": "2026-04-19T06:45:00Z"
      }
    }
  ]
}
```

3. Hydrate full video details:

```http
GET https://www.googleapis.com/youtube/v3/videos?part=snippet,status,liveStreamingDetails,contentDetails&id=abc123xyz89&key=API_KEY
```

Mock response:

```json
{
  "items": [
    {
      "id": "abc123xyz89",
      "snippet": {
        "title": "Mark 1:1-8 - Prepare the Way",
        "description": "Sunday sermon notes...\nhttps://example.com/link",
        "publishedAt": "2026-04-19T06:45:00Z",
        "liveBroadcastContent": "none"
      },
      "status": {
        "privacyStatus": "public"
      }
    }
  ]
}
```

#### Polling vs WebSub

This implementation uses scheduled polling through WP-Cron because it is simpler to host inside standard WordPress deployments and does not require a public webhook endpoint.

WebSub would reduce polling delay and quota usage, but it adds more moving parts:

- public callback endpoint management
- subscription verification handling
- lease renewal logic
- more deployment-sensitive debugging

For this church workflow, weekly scheduled sync plus a manual sync button is the safer operational default.

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
4. Recreate the `Home`, `About`, and `Contact` pages or use WP-CLI if available.
5. Go to `Settings > Permalinks`, keep the structure at `/%postname%/`, and click `Save Changes` once on the live site. Do this again after adding new public routes such as sermon taxonomies.
6. If Hostinger still shows its default 404 page for valid WordPress URLs, restore the standard WordPress rewrite rules in the document-root `.htaccess`:

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

7. Configure:
   - site title and tagline
   - homepage content in the Customizer
   - contact email, phone, address, and map URL
   - SMTP plugin
   - SEO plugin

Keep the real `wp-config.php` only on the server. This repo provides `wp-config.example.php` as a template and should not store live database credentials.

For Hostinger deployments, the repo also includes committed clean-route shims for the currently published sermon, series, and speaker URLs. They provide a file-based fallback when Apache or Hostinger routing does not pass a pretty URL into WordPress. The canonical series URLs remain `/series/{slug}/`, and `/sermons/?series={slug}` is only the temporary verification fallback if a series page starts returning a server-level 404.

The bootstrap script intentionally does not seed a saved WordPress menu. The theme fallback navigation already renders `Home`, `About`, `Sermons`, and `Contact` with environment-correct URLs. If you want a custom menu in production, create it directly in that environment and avoid importing menu items with hardcoded localhost or port-based URLs.

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
