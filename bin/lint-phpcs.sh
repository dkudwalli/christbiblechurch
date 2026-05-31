#!/usr/bin/env bash
set -euo pipefail

TOOLS_DIR="${TOOLS_DIR:-.tools}"
PHPCS_PHAR="${TOOLS_DIR}/phpcs.phar"

if [[ ! -f "${PHPCS_PHAR}" ]]; then
  mkdir -p "${TOOLS_DIR}"
  curl -fsSL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar -o "${PHPCS_PHAR}"
fi

if php -m 2>/dev/null | grep -q '^xmlwriter$' && php -m 2>/dev/null | grep -q '^SimpleXML$'; then
  php "${PHPCS_PHAR}" --standard=phpcs.xml.dist "$@"
  exit 0
fi

if command -v docker >/dev/null 2>&1; then
  docker run --rm \
    -v "${PWD}:/app" \
    -w /app \
    php:8.2-cli \
    php "${PHPCS_PHAR}" --standard=phpcs.xml.dist "$@"
  exit 0
fi

echo "Unable to run PHP_CodeSniffer: local PHP is missing xmlwriter/SimpleXML and docker is unavailable." >&2
exit 1
