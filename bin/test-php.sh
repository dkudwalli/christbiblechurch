#!/usr/bin/env bash
set -uo pipefail

# Runs the church-core PHP tests, each in its OWN process (they redeclare shared
# assert helpers, so they cannot be combined into one process).
#
# WordPress-dependent tests run inside the wp-cli container via `wp eval-file`.
# --user root makes ABSPATH writable so the route-shim tests can create their shim
# directories: locally/CI the wp-cli user (uid 82) does not own the web root
# (uid 33), whereas on production the web server process owns it.

COMPOSE="${COMPOSE:-docker compose}"

# The photo-album rewrite-upgrade test makes an HTTP request to the site. From
# inside the wp-cli container the site is reachable by compose service name, not
# 127.0.0.1; the Host header must still match the WordPress siteurl.
CHURCH_TEST_BASE_URL="${CHURCH_TEST_BASE_URL:-http://wordpress}"
CHURCH_TEST_HOST="${CHURCH_TEST_HOST:-localhost:8080}"

# Pure-logic tests: no WordPress, no database, no container start.
STANDALONE_TESTS=(
  "wp-content/plugins/church-core/tests/scripture-extractor.php"
  "wp-content/plugins/church-core/tests/sync-import-gate.php"
)

TESTS=(
  "wp-content/plugins/church-core/tests/sermon-admin-sortable-date.php"
  "wp-content/plugins/church-core/tests/photo-album-route-shims.php"
  "wp-content/plugins/church-core/tests/sermon-route-shims.php"
  "wp-content/plugins/church-core/tests/route-shim-notice.php"
  "wp-content/plugins/church-core/tests/photo-album-rewrite-upgrade.php"
)

failures=0

# PHP_BIN lets CI use the runner's php; falls back to a container when absent.
PHP_BIN="${PHP_BIN:-php}"

if ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
  PHP_BIN=""
fi

for test in "${STANDALONE_TESTS[@]}"; do
  printf '\n=== %s ===\n' "$test"

  if [ -n "${PHP_BIN}" ]; then
    run_standalone=("${PHP_BIN}" "$test")
  else
    run_standalone=(${COMPOSE} run --rm -T --entrypoint php wordpress "/var/www/html/$test")
  fi

  if "${run_standalone[@]}"; then
    :
  else
    echo "FAILED: ${test}" >&2
    failures=$((failures + 1))
  fi
done

for test in "${TESTS[@]}"; do
  printf '\n=== %s ===\n' "$test"
  if ${COMPOSE} run --rm -T --user root \
      -e CHURCH_TEST_BASE_URL="${CHURCH_TEST_BASE_URL}" \
      -e CHURCH_TEST_HOST="${CHURCH_TEST_HOST}" \
      wpcli eval-file "$test"; then
    :
  else
    echo "FAILED: ${test}" >&2
    failures=$((failures + 1))
  fi
done

if [ "${failures}" -gt 0 ]; then
  printf '\n%d PHP test file(s) failed.\n' "${failures}" >&2
  exit 1
fi

printf '\nAll PHP tests passed.\n'
