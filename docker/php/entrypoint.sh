#!/bin/sh
set -e

# Make sure runtime directories exist and are writable (storage may be a fresh mount).
mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/logs \
    bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# Cache configuration at runtime so real environment values are honoured.
if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache
fi

exec "$@"
