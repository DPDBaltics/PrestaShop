#!/usr/bin/env bash
# Prefixes the bundled Unirest namespace so other modules' Unirest copies cannot replace it.
# Run after `composer install --no-dev` when building the release zip, or with --dev after a
# full `composer install` to run tests/Unit/Build/ScopedUnirestTest.php against the scoped
# vendor. tests/ is not scoped, so other tests that use Unirest classes need an unscoped vendor,
# and tracked files that reference Unirest are rewritten in place: restore them with git after.
# Run it on a fresh `composer install`, not on an already scoped vendor. Needs PHP 8.2+ (php-scoper 0.18.18).
set -euo pipefail

cd "$(dirname "$0")/.."

PHP_SCOPER_VERSION=0.18.18
PHP_SCOPER_PHAR="$(mktemp -d)/php-scoper.phar"
PHP_SCOPER_SHA256=06d52b9b3020d06f3301803b7bc5b015f50cd1541d2c322883481b5cc40be6a2
DUMP_FLAGS=(--no-dev --optimize --classmap-authoritative)

if [ "${1:-}" = "--dev" ]; then
    DUMP_FLAGS=(--optimize)
fi

trap 'rm -rf "$(dirname "$PHP_SCOPER_PHAR")" build' EXIT

curl -sSfL -o "$PHP_SCOPER_PHAR" "https://github.com/humbug/php-scoper/releases/download/${PHP_SCOPER_VERSION}/php-scoper.phar"
if command -v sha256sum >/dev/null; then
    echo "${PHP_SCOPER_SHA256}  ${PHP_SCOPER_PHAR}" | sha256sum -c -
else
    echo "${PHP_SCOPER_SHA256}  ${PHP_SCOPER_PHAR}" | shasum -a 256 -c -
fi

rm -rf build
php "$PHP_SCOPER_PHAR" add-prefix --output-dir=build --force --no-interaction
rm -f build/composer.json
(cd build && tar cf - .) | tar xf -
rm -rf build vendor/scoper-autoload.php
composer dump-autoload "${DUMP_FLAGS[@]}"

CLASSMAP=vendor/composer/autoload_classmap.php
if grep -q "'Unirest\\\\\\\\" "$CLASSMAP" || ! grep -q "'Invertus\\\\\\\\dpdBaltics\\\\\\\\Vendor\\\\\\\\Unirest\\\\\\\\Request'" "$CLASSMAP"; then
    echo "Unirest was not scoped, check that php-scoper ran on a supported PHP version" >&2
    exit 1
fi

# Docblocks keep the original name, so comment lines are skipped and only code references are checked.
UNSCOPED=$(grep -rEn --include='*.php' --exclude-dir=tests --exclude-dir=test --exclude-dir=Tests \
    '^\s*namespace\s+Unirest\b|Unirest\\[A-Za-z]' src vendor controllers upgrade dpdbaltics.php \
    | grep -vE 'Vendor\\+Unirest' | grep -vE '^[^:]+:[0-9]+:\s*(\*|//|/\*|#)' || true)
if [ -n "$UNSCOPED" ]; then
    echo "$UNSCOPED" >&2
    echo "Unscoped Unirest reference left in code" >&2
    exit 1
fi
