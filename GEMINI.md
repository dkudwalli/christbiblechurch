# Project Overview

This is a WordPress starter repository for a church website. It features a Docker-based local environment with WordPress on PHP 8.2 and MySQL 8.

The project's architecture is focused on a custom plugin (`wp-content/plugins/church-core`) and a custom theme (`wp-content/themes/church-theme`). The plugin provides the primary content model, including a `sermon` custom post type, `series` and `speaker` taxonomies, and a built-in contact form. WordPress core files are intentionally excluded from the repository to facilitate a pull-based deployment strategy to managed WordPress hosts (like Hostinger).

# Building and Running

**Local Setup:**
The project uses Docker Compose for the local environment.

1. Ensure `.env` is created from `.env.example`.
2. Start the local database and WordPress containers, install WordPress, activate the custom theme and plugin, and seed initial pages by running the bootstrap script:
   ```bash
   ./bin/bootstrap-wordpress.sh
   ```
3. The site will be available at `http://localhost:8080` (or the port defined in `.env`).

**Useful Commands:**
- Validate Docker compose configuration: `docker compose config`
- Access WP-CLI within the container: `docker compose run --rm wpcli shell`

# Development Conventions

- **Source Control:** Only custom code (the `church-theme`, `church-core` plugin, route shims, and configuration scripts) should be committed. **Do not** commit WordPress core files, bundled plugins, or the `wp-content/uploads` directory.
- **Routing:** The site relies on specific clean-route shims (`about/`, `contact/`, `sermons/`, `series/`, and `speaker/`) as file-based fallbacks for Hostinger deployments. Ensure permalink structure is kept at `/%postname%/`.
- **Content Model:** Sermons are managed via the custom post type and use taxonomies for series and speakers. Contact form submissions are saved internally as "Contact Messages" and use `wp_mail()` for notifications.
- **Deployments:** Deployments to production should only upload the custom theme and plugin to an existing live WordPress installation. Keep production configurations (like the real `wp-config.php`, SMTP plugins, and SEO tools) strictly on the server.