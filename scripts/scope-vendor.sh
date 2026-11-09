#!/usr/bin/env bash
# Prefixes the bundled Unirest namespace so other modules' Unirest copies cannot replace it.
# Run after `composer install --no-dev` when building the release zip, or with --dev after a
# full `composer install` to run tests/Unit/Build/ScopedUnirestTest.php against the scoped
# vendor. tests/ is not scoped, so other tests that use Unirest classes need an unscoped vendor.
# Needs PHP 8.1+.
set -euo pipefail

cd "$(dirname "$0")/.."

PHP_SCOPER_VERSION=0.18.18
PHP_SCOPER_PHAR=php-scoper.phar
PHP_SCOPER_SHA256=06d52b9b3020d06f3301803b7bc5b015f50cd1541d2c322883481b5cc40be6a2
DUMP_FLAGS=(--no-dev --optimize --classmap-authoritative)

if [ "${1:-}" = "--dev" ]; then
    DUMP_FLAGS=(--optimize)
fi

if [ ! -f "$PHP_SCOPER_PHAR" ]; then
    curl -sSfL -o "$PHP_SCOPER_PHAR" "https://github.com/humbug/php-scoper/releases/download/${PHP_SCOPER_VERSION}/php-scoper.phar"
fi
echo "${PHP_SCOPER_SHA256}  ${PHP_SCOPER_PHAR}" | shasum -a 256 -c -

rm -rf build
php "$PHP_SCOPER_PHAR" add-prefix --output-dir=build --force --no-interaction
rm -f build/composer.json
(cd build && tar cf - .) | tar xf -
rm -rf build "$PHP_SCOPER_PHAR" vendor/scoper-autoload.php
composer dump-autoload "${DUMP_FLAGS[@]}"

CLASSMAP=vendor/composer/autoload_classmap.php
if grep -q "'Unirest\\\\\\\\" "$CLASSMAP" || ! grep -q "'Invertus\\\\\\\\dpdBaltics\\\\\\\\Vendor\\\\\\\\Unirest\\\\\\\\Request'" "$CLASSMAP"; then
    echo "Unirest was not scoped, check that php-scoper ran on a supported PHP version" >&2
    exit 1
fi

# Docblocks keep the original name, so only code that would load or catch a Unirest class is checked.
if grep -rEin --include='*.php' --exclude-dir=tests --exclude-dir=test --exclude-dir=Tests \
    '^\s*(namespace|use)\s+\\?Unirest\b|(new|catch\s*\(|extends|instanceof)\s*\\?Unirest\\|[^\\A-Za-z_]Unirest\\[A-Za-z\\]+::' \
    src vendor controllers upgrade dpdbaltics.php; then
    echo "Unscoped Unirest reference left in code" >&2
    exit 1
fi
