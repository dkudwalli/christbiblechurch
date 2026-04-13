#!/usr/bin/env bash
set -euo pipefail

COMPOSE="docker compose"
SITE_URL="${SITE_URL:-http://localhost:${WORDPRESS_PORT:-8080}}"
SITE_TITLE="${SITE_TITLE:-Christ Bible Church}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-changeme123!}"
ADMIN_EMAIL="${ADMIN_EMAIL:-hello@example.com}"

run_wp() {
  ${COMPOSE} run --rm wpcli "$@"
}

find_page_id() {
  local slug="$1"
  run_wp post list --post_type=page --name="${slug}" --field=ID --posts_per_page=1 2>/dev/null || true
}

ensure_page() {
  local slug="$1"
  local title="$2"
  local content="$3"
  local page_id

  page_id="$(find_page_id "${slug}" | tr -d '\r')"

  if [[ -z "${page_id}" ]]; then
    run_wp post create \
      --post_type=page \
      --post_status=publish \
      --post_name="${slug}" \
      --post_title="${title}" \
      --post_content="${content}" \
      --porcelain
  else
    printf '%s\n' "${page_id}"
  fi
}

echo "Starting local WordPress services..."
${COMPOSE} up -d db wordpress

echo "Ensuring wp-content/uploads exists..."
${COMPOSE} exec wordpress bash -lc 'mkdir -p /var/www/html/wp-content/uploads && chown -R www-data:www-data /var/www/html/wp-content/uploads'

echo "Waiting for WordPress files to become available..."
until run_wp core version >/dev/null 2>&1; do
  sleep 3
done

if ! run_wp core is-installed >/dev/null 2>&1; then
  echo "Installing WordPress..."
  run_wp core install \
    --url="${SITE_URL}" \
    --title="${SITE_TITLE}" \
    --admin_user="${ADMIN_USER}" \
    --admin_password="${ADMIN_PASSWORD}" \
    --admin_email="${ADMIN_EMAIL}" \
    --skip-email
fi

echo "Activating theme and plugin..."
run_wp theme activate church-theme
run_wp plugin activate church-core
run_wp option update blogname "${SITE_TITLE}"
run_wp option update blogdescription 'Exalting Christ by Making Disciples.'
run_wp option update permalink_structure '/%postname%/'
run_wp rewrite flush --hard

HOME_CONTENT=$'<!-- wp:paragraph --><p>Welcome to our church community.</p><!-- /wp:paragraph -->'
ABOUT_CONTENT=$'<!-- wp:paragraph --><p>This page is rendered by the theme&#8217;s dedicated About template. Update the structured doctrine and leadership content in the theme code if needed.</p><!-- /wp:paragraph -->'
CONTACT_CONTENT=$'<!-- wp:paragraph --><p>Use the contact details or form to reach us.</p><!-- /wp:paragraph -->'

HOME_ID="$(ensure_page home 'Home' "${HOME_CONTENT}")"
ABOUT_ID="$(ensure_page about 'About' "${ABOUT_CONTENT}")"
CONTACT_ID="$(ensure_page contact 'Contact' "${CONTACT_CONTENT}")"

run_wp post update "${ABOUT_ID}" --post_content="${ABOUT_CONTENT}" --post_status=publish >/dev/null

WHAT_WE_TEACH_ID="$(find_page_id 'what-we-teach' | tr -d '\r')"

if [[ -n "${WHAT_WE_TEACH_ID}" ]]; then
  run_wp post update "${WHAT_WE_TEACH_ID}" --post_status=draft >/dev/null
fi

run_wp option update show_on_front page
run_wp option update page_on_front "${HOME_ID}"
run_wp eval '
$locations = get_theme_mod("nav_menu_locations", []);
if (! is_array($locations)) {
    $locations = [];
}
$locations["primary"] = 0;
set_theme_mod("nav_menu_locations", $locations);
' >/dev/null

if [[ -z "$(run_wp term list speaker --field=slug 2>/dev/null || true)" ]]; then
  run_wp term create speaker 'Pastor Daniel' --slug=pastor-daniel >/dev/null
fi

if [[ -z "$(run_wp term list series --field=slug 2>/dev/null || true)" ]]; then
  run_wp term create series 'Living Hope' --slug=living-hope --description='A short sample series about finding steady hope in Christ through uncertain seasons.' >/dev/null
fi

if [[ -z "$(run_wp post list --post_type=sermon --field=ID --posts_per_page=1 2>/dev/null || true)" ]]; then
  SAMPLE_SERMON_ID="$(run_wp post create \
    --post_type=sermon \
    --post_status=publish \
    --post_title='Hope in Uncertain Times' \
    --post_content='<!-- wp:paragraph --><p>A starter sermon entry with room for notes, application points, and Scripture reflections.</p><!-- /wp:paragraph -->' \
    --porcelain)"

  run_wp post meta update "${SAMPLE_SERMON_ID}" sermon_date "$(date +%F)" >/dev/null
  run_wp post meta update "${SAMPLE_SERMON_ID}" scripture_reference 'Romans 8:28-39' >/dev/null
  run_wp post meta update "${SAMPLE_SERMON_ID}" youtube_url 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' >/dev/null
  run_wp post meta update "${SAMPLE_SERMON_ID}" audio_url '' >/dev/null
  run_wp post term set "${SAMPLE_SERMON_ID}" speaker pastor-daniel >/dev/null
  run_wp post term set "${SAMPLE_SERMON_ID}" series living-hope >/dev/null
fi

echo
echo "Local WordPress is ready."
echo "Site: ${SITE_URL}"
echo "Admin: ${SITE_URL%/}/wp-admin/"
echo "Navigation: using the theme fallback menu until a menu is created in this environment."
echo "Username: ${ADMIN_USER}"
echo "Password: ${ADMIN_PASSWORD}"
