#!/bin/sh
set -eu

mkdir -p var/cache var/log config/jwt

if [ -n "${JWT_PRIVATE_KEY_B64:-}" ]; then
  printf '%s' "$JWT_PRIVATE_KEY_B64" | tr -d '\r\n ' | base64 -d > config/jwt/private.pem
fi

if [ -n "${JWT_PUBLIC_KEY_B64:-}" ]; then
  printf '%s' "$JWT_PUBLIC_KEY_B64" | tr -d '\r\n ' | base64 -d > config/jwt/public.pem
fi

chown -R www-data:www-data var config/jwt

if [ "${APP_ENV:-prod}" = "prod" ]; then
  if [ "${RUN_MIGRATIONS_ON_START:-0}" = "1" ]; then
    php bin/console doctrine:migrations:sync-metadata-storage --no-interaction || true
    php bin/console doctrine:migrations:migrate --no-interaction
  fi
  php bin/console cache:clear --env=prod --no-debug --no-warmup || true
  php bin/console cache:warmup --env=prod --no-debug || true
fi

exec "$@"
