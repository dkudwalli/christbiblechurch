#!/usr/bin/env bash
set -euo pipefail

COMPOSE="docker compose"
SITE_URL="${SITE_URL:-http://localhost:${WORDPRESS_PORT:-8080}}"
SITE_TITLE="${SITE_TITLE:-Crossroad South Church}"
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
  local parent_id="${4:-0}"
  local menu_order="${5:-0}"
  local page_id

  page_id="$(find_page_id "${slug}" | tr -d '\r')"

  if [[ -z "${page_id}" ]]; then
    page_id="$(run_wp post create \
      --post_type=page \
      --post_status=publish \
      --post_name="${slug}" \
      --post_title="${title}" \
      --post_content="${content}" \
      --post_parent="${parent_id}" \
      --menu_order="${menu_order}" \
      --porcelain)"
  else
    run_wp post update "${page_id}" \
      --post_status=publish \
      --post_name="${slug}" \
      --post_title="${title}" \
      --post_content="${content}" \
      --post_parent="${parent_id}" \
      --menu_order="${menu_order}" >/dev/null
  fi

  printf '%s\n' "${page_id}"
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
run_wp option update blogdescription 'Exalting the Triune God, Edifying Believers, Evangelizing the Unreached.'
run_wp option update home "${SITE_URL}"
run_wp option update siteurl "${SITE_URL}"
run_wp option update permalink_structure '/%postname%/'
run_wp rewrite flush --hard
run_wp theme mod set hero_eyebrow '' >/dev/null
run_wp theme mod set contact_phone '+919663065363' >/dev/null
run_wp theme mod set contact_email 'crossroadsouthchurch@gmail.com' >/dev/null
run_wp theme mod set service_times $'Corporate Worship - Sunday 10 am\nWednesday Bible Study at 7:30pm - Online Meeting\nOffice Hours - Tuesday to Saturday 9:30 am - 5:30 pm' >/dev/null

HOME_CONTENT="$(cat <<'HTML'
<p>Crossroad South Church is a Gospel-centered church in South Bangalore. This page is rendered by the theme front page and serves as the public landing page for visitors.</p>
HTML
)"

ABOUT_CONTENT="$(cat <<'HTML'
<p>Crossroad South Church is a community of Christ-followers from diverse linguistic, geographic, and cultural backgrounds. We gather to glorify the Triune God, enjoy Him together, and grow in faithful Christian discipleship.</p>
<p>The sections below summarize the beliefs, mission priorities, leadership, governance, and core values that shape our life together as a local church.</p>
HTML
)"

BELIEFS_CONTENT="$(cat <<'HTML'
<p>What follows is our understanding of the basic essentials of the biblical, orthodox Christian faith. This section functions as a concise doctrinal statement for the church.</p>
<h3>The Bible</h3>
<p>We believe that Scripture is the inspired, infallible, and authoritative Word of God, sufficient for all matters of faith and practice.</p>
<h3>God</h3>
<p>There is one true and living God who eternally exists in three persons: Father, Son, and Holy Spirit, each fully God and equal in essence.</p>
<h3>Jesus Christ</h3>
<p>Jesus Christ, the eternal Son of God, took on human flesh, lived a sinless life, died in the place of sinners, rose bodily from the dead, and reigns as Lord and King.</p>
<h3>The Holy Spirit</h3>
<p>The Holy Spirit regenerates sinners, indwells believers, convicts of sin, and empowers God’s people for holy living and witness.</p>
<h3>Salvation</h3>
<p>Salvation is by grace alone, through faith alone, in Christ alone. Sinners are justified apart from works and are kept secure by the finished work of Christ.</p>
<h3>The Church</h3>
<p>The Church is the body of Christ, called to worship God, preach the gospel, administer baptism and the Lord’s Supper, and make disciples.</p>
<h3>The Future</h3>
<p>Christ will return personally and visibly to judge the living and the dead, gather His people, and establish His everlasting kingdom.</p>
HTML
)"

MISSIONS_CONTENT="$(cat <<'HTML'
<p>The New Testament church displayed grace-filled generosity toward the wider work of the gospel. Crossroad South Church considers such participation essential to maturing as a gospel-centered church.</p>
<p>The live church site notes that the church currently supports four mission-related projects. This page provides the section structure now and can be expanded with named partners and reports as those details are curated.</p>
HTML
)"

ELDER_BOARD_CONTENT="$(cat <<'HTML'
<h3>Benjamin Stephen, Pastor In-Charge</h3>
<p>Benji, completed his MDiv in New Testament studies from SAIACS. Prior to moving as a church planter to the south of Bangalore he served with Crossroad Church as an Executive Pastor (<a href="https://www.crossroadbangalore.org/">www.crossroadbangalore.org</a>). Prior to taking up theological studies he worked in the Biopharmaceutical industry for 12 years. His interests are primarily in the areas of music and reading. Married to Rashmi, they are blessed with two children, Gabriel and Jessica.</p>
<p>Rashmi is a school teacher, leads the Women's group while serving in other ministries of the church. Benji works with Langham Preaching, as the Operations Manager for Asia &amp; South Pacific.</p>
<h3>Timothy Connors, Elder-Pastor</h3>
<p>Timothy, more fondly called as Timmy moved from Chennai to Bangalore in the year 2000, to work at Breakthrough. He has a personal vision to impact lives through creative means. Along with his supporting wife he makes it his mission to encourage and enable others' success in whatever way possible, even through humor. Timmy is married to Ruth and are blessed with two children, Caleb and Debbie.</p>
<p>As a family they jointly strive, to the extent possible, to be leaders of influence, serving for the Kingdom of God in whatever way possible. Ruth leads the Intercessory prayer ministry and part of the praise ministry.</p>
<h3>Kishore Hanani, Elder-Pastor</h3>
<p>Kishore Hanani is married to Shirley. Both of them are employed in the IT sector. Both have a desire to serve the purposes of Christ through the church. Kishore takes care of the praise and worship ministry at church and Shirley is involved with the Sunday School. Kishore has interests in music and reading. Shirley, loves travel. They are blessed with two children, Nihal (8 yrs) and Samaira (4.5 yrs).</p>
HTML
)"

GOVERNANCE_CONTENT="$(cat <<'HTML'
<p>The church is governed by a male elder board in which the pastor in charge serves as first among equals. Regular board meetings review spiritual health, finances, missions, and the broader direction of the church.</p>
<p>The appointment of an elder follows the scriptural guidelines of 1 Timothy 3 and Titus 1 along with formal agreement on the church’s doctrinal commitments. The church also relies on staff and volunteers who coordinate weekly worship preparation and congregational life.</p>
HTML
)"

CORE_VALUES_CONTENT="$(cat <<'HTML'
<p>Crossroad South Church’s core values are breaking down barriers, gospel-centered living, deep biblical conviction, and missional engagement.</p>
<h3>Breaking Down Barriers</h3>
<p>The church aims to welcome people across linguistic, geographic, and cultural backgrounds into meaningful life together under Christ.</p>
<h3>Gospel-Centered</h3>
<p>All our dealings within and outside the community of faith should bear witness to the grace we ourselves have received.</p>
<h3>Deeply Biblical</h3>
<p>Preaching, teaching, discipleship, and decision-making are shaped by the authority and sufficiency of Scripture.</p>
<h3>Missional Living</h3>
<p>The church wants every member to see ordinary life as a setting for witness, service, hospitality, and gospel mission.</p>
HTML
)"

WORSHIP_CONTENT="$(cat <<'HTML'
<p>Corporate worship and small groups are conducted in English. Crossroad South Church is kids, youth, and adult friendly, and the page sections below explain how worship and age-group ministries currently function.</p>
HTML
)"

CORPORATE_WORSHIP_CONTENT="$(cat <<'HTML'
<p>The corporate worship service is held each Sunday at 10 am. The gathering includes a time of intercession, praise, and the preaching of God’s Word.</p>
HTML
)"

KIDS_MINISTRY_CONTENT="$(cat <<'HTML'
<p>Kingdom Warriors is the children’s ministry of Crossroad South Church.</p>
<p>Children in the age groups 3–7 and 8–13 meet for Sunday School during the sermon portion of the main worship service. The curriculum is built in house and follows a parallel track with what is preached in the main gathering.</p>
HTML
)"

TEENS_MINISTRY_CONTENT="$(cat <<'HTML'
<p>Teens meet every alternate Sunday during the preaching time. The gathering centers on studying God’s Word and applying it in daily discipleship.</p>
HTML
)"

WOMENS_MINISTRY_CONTENT="$(cat <<'HTML'
<p>Women meet every alternate Sunday immediately after the service for learning from God’s Word and for prayer together.</p>
HTML
)"

GALLERY_CONTENT="$(cat <<'HTML'
<p>Browse photos and updates from Crossroad South Church here. The page is ready for a live Instagram-connected gallery and will safely fall back until the account access is added.</p>
HTML
)"

GIVE_CONTENT="$(cat <<'HTML'
<p>Crossroad South Church is supported by the cheerful generosity of its members and friends. If you would like to give, use the transfer details below or contact the church for help.</p>
<h3>Bank Transfer Information</h3>
<ul>
  <li><strong>Bank:</strong> Bank of Baroda, Banashankari Branch</li>
  <li><strong>A/C Name:</strong> Crossroad South</li>
  <li><strong>A/C No:</strong> 73620100002899</li>
  <li><strong>IFSC:</strong> BARB0VJBANA</li>
  <li><strong>MICR:</strong> 560012193</li>
</ul>
<p>Because of a bank merger, the live site notes that the church’s bank details were updated. Confirm with the church office if you need the latest instructions before transferring.</p>
HTML
)"

CONTACT_CONTENT="$(cat <<'HTML'
<p>We would love to hear from you. Reach out before your first visit if you need directions, want more detail on Sunday ministries, or have a question about Crossroad South Church.</p>
HTML
)"

HOME_ID="$(ensure_page home 'Home' "${HOME_CONTENT}" 0 0)"
ABOUT_ID="$(ensure_page about-us 'About Us' "${ABOUT_CONTENT}" 0 10)"
BELIEFS_ID="$(ensure_page beliefs 'Beliefs' "${BELIEFS_CONTENT}" "${ABOUT_ID}" 10)"
MISSIONS_ID="$(ensure_page missions 'Missions' "${MISSIONS_CONTENT}" "${ABOUT_ID}" 20)"
ELDER_BOARD_ID="$(ensure_page elder-board 'Elder Board' "${ELDER_BOARD_CONTENT}" "${ABOUT_ID}" 30)"
GOVERNANCE_ID="$(ensure_page governance 'Governance' "${GOVERNANCE_CONTENT}" "${ABOUT_ID}" 40)"
CORE_VALUES_ID="$(ensure_page core-values 'Core Values' "${CORE_VALUES_CONTENT}" "${ABOUT_ID}" 50)"
WORSHIP_ID="$(ensure_page worship 'Worship' "${WORSHIP_CONTENT}" 0 20)"
CORPORATE_WORSHIP_ID="$(ensure_page corporate-worship 'Corporate Worship' "${CORPORATE_WORSHIP_CONTENT}" "${WORSHIP_ID}" 10)"
KIDS_MINISTRY_ID="$(ensure_page kids-ministry 'Kingdom Warriors' "${KIDS_MINISTRY_CONTENT}" "${WORSHIP_ID}" 20)"
TEENS_MINISTRY_ID="$(ensure_page teens-ministry 'Teens Ministry' "${TEENS_MINISTRY_CONTENT}" "${WORSHIP_ID}" 30)"
WOMENS_MINISTRY_ID="$(ensure_page womens-ministry "Women's Ministry" "${WOMENS_MINISTRY_CONTENT}" "${WORSHIP_ID}" 40)"
GALLERY_ID="$(ensure_page gallery 'Gallery' "${GALLERY_CONTENT}" 0 30)"
GIVE_ID="$(ensure_page give 'Give' "${GIVE_CONTENT}" 0 40)"
CONTACT_ID="$(ensure_page contact-us 'Contact Us' "${CONTACT_CONTENT}" 0 50)"

for legacy_slug in about contact events what-we-teach sample-page; do
  LEGACY_ID="$(find_page_id "${legacy_slug}" | tr -d '\r')"

  if [[ -n "${LEGACY_ID}" ]]; then
    run_wp post update "${LEGACY_ID}" --post_status=draft >/dev/null
  fi
done

run_wp option update show_on_front page
run_wp option update page_on_front "${HOME_ID}"

run_wp eval "
\$menu_name = 'Primary Navigation';
\$menu = wp_get_nav_menu_object(\$menu_name);
\$menu_id = \$menu ? (int) \$menu->term_id : (int) wp_create_nav_menu(\$menu_name);
\$items = wp_get_nav_menu_items(\$menu_id);
if (is_array(\$items)) {
    foreach (\$items as \$item) {
        wp_delete_post((int) \$item->ID, true);
    }
}
\$add_custom = static function (string \$title, string \$url, int \$parent = 0) use (\$menu_id): int {
    return (int) wp_update_nav_menu_item(\$menu_id, 0, [
        'menu-item-title' => \$title,
        'menu-item-url' => \$url,
        'menu-item-status' => 'publish',
        'menu-item-type' => 'custom',
        'menu-item-parent-id' => \$parent,
    ]);
};
\$add_page = static function (int \$post_id, int \$parent = 0) use (\$menu_id): int {
    return (int) wp_update_nav_menu_item(\$menu_id, 0, [
        'menu-item-object-id' => \$post_id,
        'menu-item-object' => 'page',
        'menu-item-status' => 'publish',
        'menu-item-type' => 'post_type',
        'menu-item-parent-id' => \$parent,
    ]);
};
\$home_item = \$add_custom('Home', home_url('/'));
\$about_item = \$add_page((int) ${ABOUT_ID});
\$add_custom('Beliefs', trailingslashit(get_permalink((int) ${ABOUT_ID})) . '#beliefs', \$about_item);
\$add_custom('Missions', trailingslashit(get_permalink((int) ${ABOUT_ID})) . '#missions', \$about_item);
\$add_custom('Elder Board', trailingslashit(get_permalink((int) ${ABOUT_ID})) . '#elder-board', \$about_item);
\$add_custom('Governance', trailingslashit(get_permalink((int) ${ABOUT_ID})) . '#governance', \$about_item);
\$add_custom('Core Values', trailingslashit(get_permalink((int) ${ABOUT_ID})) . '#core-values', \$about_item);
\$worship_item = \$add_page((int) ${WORSHIP_ID});
\$add_custom('Corporate Worship', trailingslashit(get_permalink((int) ${WORSHIP_ID})) . '#corporate-worship', \$worship_item);
\$add_custom('Kids Ministry', trailingslashit(get_permalink((int) ${WORSHIP_ID})) . '#kids-ministry', \$worship_item);
\$add_custom('Teens Ministry', trailingslashit(get_permalink((int) ${WORSHIP_ID})) . '#teens-ministry', \$worship_item);
\$add_page((int) ${GALLERY_ID});
\$add_page((int) ${GIVE_ID});
\$add_custom('Sermons', get_post_type_archive_link('sermon') ?: home_url('/sermons/'));
\$add_page((int) ${CONTACT_ID});
\$locations = get_theme_mod('nav_menu_locations', []);
if (! is_array(\$locations)) {
    \$locations = [];
}
\$locations['primary'] = \$menu_id;
set_theme_mod('nav_menu_locations', \$locations);
" >/dev/null

run_wp rewrite flush --hard >/dev/null

echo
echo "Local WordPress is ready."
echo "Site: ${SITE_URL}"
echo "Admin: ${SITE_URL%/}/wp-admin/"
echo "Navigation: Primary Navigation menu created and assigned."
echo "Username: ${ADMIN_USER}"
echo "Password: ${ADMIN_PASSWORD}"
