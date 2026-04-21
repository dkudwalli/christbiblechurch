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
run_wp theme mod set contact_phone '+919663065363' >/dev/null
run_wp theme mod set contact_email 'crossroadsouthchurch@gmail.com' >/dev/null
run_wp theme mod set service_times $'Corporate Worship - Sunday 10 am\nWednesday Bible Study at 7:30pm - Online Meeting\nOffice Hours - Tuesday to Saturday 9:30 am - 5:30 pm' >/dev/null

HOME_CONTENT="$(cat <<'HTML'
<p>Crossroad South Church is a Gospel-centered church in South Bangalore. This page is rendered by the theme front page and serves as the public landing page for visitors.</p>
HTML
)"

ABOUT_CONTENT="$(cat <<'HTML'
<p>Crossroad South Church is a community of Christ-followers from diverse linguistic, geographic, and cultural backgrounds. We gather to glorify the Triune God, enjoy Him together, and grow in faithful Christian discipleship.</p>
<p>The sections below summarize the beliefs, mission priorities, leadership, governance, stewardship, membership, and core values that shape our life together as a local church.</p>
HTML
)"

BELIEFS_CONTENT="$(cat <<'HTML'
<p>What follows is our understanding of the basic essentials of the biblical, orthodox Christian faith. We regard these as theological convictions commonly held by Christ-followers throughout the history of Christian thought and teaching, as derived from the sixty-six books of the Bible.</p>
<h3>The Bible</h3>
<p>We believe the Bible, both the Old and New Testaments, to be the inspired Word of God, without error in the original writings, the complete revelation of God’s will for the salvation of men, and the divine and final authority for all Christian faith and practice.</p>
<h3>God</h3>
<p>We believe in one God, Creator of all things, infinitely perfect and eternally existing in three persons: Father, Son, and Holy Spirit.</p>
<h3>Jesus Christ</h3>
<p>We believe that Jesus Christ is true God and true man, conceived of the Holy Spirit and born of the Virgin Mary. He died on the cross as a sacrifice for our sins according to the Scriptures, was bodily raised on the third day, ascended into heaven, and now serves as our High Priest and Advocate.</p>
<h3>The Holy Spirit</h3>
<p>We believe that the ministry of the Holy Spirit is to glorify the Lord Jesus Christ and, in this age, to convict, regenerate, indwell, guide, instruct, and empower the believer for godly living and service.</p>
<h3>Man</h3>
<p>We believe that man was created in the image of God but fell into sin and is therefore eternally separated from God. Only through regeneration by the Holy Spirit can salvation and eternal spiritual life be obtained.</p>
<h3>Cross</h3>
<p>We believe that the blood of Jesus Christ, shed in His death on the cross, together with His resurrection from the dead, provides the only ground for justification and salvation for all who believe. Those who receive Jesus Christ are born of the Holy Spirit and become children of God.</p>
<h3>Ordinances</h3>
<p>We believe that water baptism and the Lord’s Supper are ordinances to be observed by the church during the present age. They are not, however, to be regarded as means of salvation.</p>
HTML
)"

MISSIONS_CONTENT="$(cat <<'HTML'
<p>We believe the New Testament church showed much grace in contributions, whether small or large, toward the wider needs of the mission work happening around them. As a church we consider such participation essential to our maturing as a gospel-centered church.</p>
<p>Crossroad South Church currently supports four mission-related projects and desires to keep growing in generous, outward-facing partnership for the sake of the gospel.</p>
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
<p>The church is governed by a male elder board in which the pastor in charge is considered first among equals. Monthly board meetings review matters related to spiritual health, finances, missions, and the broader direction of the church.</p>
<p>The appointment of an elder follows the scriptural guidelines of 1 Timothy 3 and Titus 1 together with a more formal review of the church’s doctrinal understandings.</p>
<p>Apart from the elder board, the church has paid staff and also relies heavily on volunteers who give their time and talent to weekly ministry. A working committee meets regularly to deliberate on aspects of the worship service such as praise and worship, announcements, and preaching, reviewing the previous Sunday and preparing for the following Sunday.</p>
HTML
)"

STEWARDSHIP_CONTENT="$(cat <<'HTML'
<p>The giving offered on Sundays and at other times is understood as stewardship. We believe New Testament teaching on giving calls individuals to cheerfully extend their resources, exercising faith in the work of the church and participating in the expansion of the kingdom of God.</p>
<p>Financial integrity and accountability are a high priority in the church. Transparency in bookkeeping and accounts should be in place, allowing members to access financial information for valid reasons.</p>
<p>From time to time, preferably each quarter, the financial health of the church is presented to the members.</p>
HTML
)"

MEMBERSHIP_CONTENT="$(cat <<'HTML'
<p>All are welcome to become members of the community of faith. Those desiring membership are expected to align with the mission, vision, values, and beliefs of the church, which includes attending a membership class.</p>
<p>Membership is then expressed through active and regular participation in Sunday services, Life Groups, and biblical stewardship.</p>
HTML
)"

CORE_VALUES_CONTENT="$(cat <<'HTML'
<h3>Gospel-Centered</h3>
<p>All our dealings within and outside the community of faith should witness to the grace of which we ourselves have been recipients.</p>
<h3>Deeply Biblical</h3>
<p>The Word of God is studied, taught, and preached with the understanding that its right interpretation and application are crucial to the health of the church.</p>
<h3>Breaking Down Barriers</h3>
<p>The church should reflect the nature of the New Testament church where all are considered equally loved and cared for by the Triune Lord.</p>
<h3>Missional Living</h3>
<p>Every individual should exemplify the witness of the gospel wherever they may be: in the neighbourhood, school, college, or workplace.</p>
HTML
)"

WORSHIP_CONTENT="$(cat <<'HTML'
<p>Corporate worship and small groups are conducted in English. The sections below explain how Sunday worship, Life Groups, age-group ministries, and periodic ministries currently function in the life of Crossroad South Church.</p>
HTML
)"

CORPORATE_WORSHIP_CONTENT="$(cat <<'HTML'
<p>We believe the whole time spent in the presence of the Triune Lord on a Sunday worship service or any other community gathering is centered on Him.</p>
<p>This means that singing, preaching, teaching, fasting, studying, and giving are drawn from Him and yielded back to Him as a response, because we are His children.</p>
<p>The corporate worship service is currently held each Sunday at 10 am and includes a time of intercession, praise, and the preaching of God’s Word.</p>
HTML
)"

PRIMARY_MINISTRIES_CONTENT="$(cat <<'HTML'
<p>We believe that discipleship is key to the Great Commission and that these ministries happen especially through the pulpit ministry and the Life Group.</p>
<p>The Life Group is intended to be a community in which we become vulnerable and available, being influenced together by the study of God’s Word.</p>
<p>These primary ministries also include the church’s ongoing ministry to children and young adults.</p>
HTML
)"

SECONDARY_MINISTRIES_CONTENT="$(cat <<'HTML'
<p>These are ministries that happen from time to time, addressing themes such as parenting, marriage, book studies, and related areas of Christian life and discipleship.</p>
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
STEWARDSHIP_ID="$(ensure_page stewardship 'Stewardship' "${STEWARDSHIP_CONTENT}" "${ABOUT_ID}" 50)"
MEMBERSHIP_ID="$(ensure_page membership 'Membership' "${MEMBERSHIP_CONTENT}" "${ABOUT_ID}" 60)"
CORE_VALUES_ID="$(ensure_page core-values 'Core Values' "${CORE_VALUES_CONTENT}" "${ABOUT_ID}" 70)"
WORSHIP_ID="$(ensure_page worship 'Worship' "${WORSHIP_CONTENT}" 0 20)"
CORPORATE_WORSHIP_ID="$(ensure_page corporate-worship 'Corporate Worship' "${CORPORATE_WORSHIP_CONTENT}" "${WORSHIP_ID}" 10)"
PRIMARY_MINISTRIES_ID="$(ensure_page primary-ministries 'Primary Ministries' "${PRIMARY_MINISTRIES_CONTENT}" "${WORSHIP_ID}" 20)"
SECONDARY_MINISTRIES_ID="$(ensure_page secondary-ministries 'Secondary Ministries' "${SECONDARY_MINISTRIES_CONTENT}" "${WORSHIP_ID}" 30)"
KIDS_MINISTRY_ID="$(ensure_page kids-ministry 'Kingdom Warriors' "${KIDS_MINISTRY_CONTENT}" "${WORSHIP_ID}" 40)"
TEENS_MINISTRY_ID="$(ensure_page teens-ministry 'Teens Ministry' "${TEENS_MINISTRY_CONTENT}" "${WORSHIP_ID}" 50)"
WOMENS_MINISTRY_ID="$(ensure_page womens-ministry "Women's Ministry" "${WOMENS_MINISTRY_CONTENT}" "${WORSHIP_ID}" 60)"
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
\$add_custom('Stewardship', trailingslashit(get_permalink((int) ${ABOUT_ID})) . '#stewardship', \$about_item);
\$add_custom('Membership', trailingslashit(get_permalink((int) ${ABOUT_ID})) . '#membership', \$about_item);
\$add_custom('Core Values', trailingslashit(get_permalink((int) ${ABOUT_ID})) . '#core-values', \$about_item);
\$worship_item = \$add_page((int) ${WORSHIP_ID});
\$add_custom('Corporate Worship', trailingslashit(get_permalink((int) ${WORSHIP_ID})) . '#corporate-worship', \$worship_item);
\$add_custom('Primary Ministries', trailingslashit(get_permalink((int) ${WORSHIP_ID})) . '#primary-ministries', \$worship_item);
\$add_custom('Secondary Ministries', trailingslashit(get_permalink((int) ${WORSHIP_ID})) . '#secondary-ministries', \$worship_item);
\$add_custom('Kids Ministry', trailingslashit(get_permalink((int) ${WORSHIP_ID})) . '#kids-ministry', \$worship_item);
\$add_custom('Teens Ministry', trailingslashit(get_permalink((int) ${WORSHIP_ID})) . '#teens-ministry', \$worship_item);
\$add_custom('Women\'s Ministry', trailingslashit(get_permalink((int) ${WORSHIP_ID})) . '#womens-ministry', \$worship_item);
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
