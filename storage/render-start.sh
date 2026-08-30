#!/usr/bin/env bash
set -euo pipefail

# Script for Render: write the CA PEM from CA_PEM_BASE64 to storage/certs/ca.pem
# then clear config and start the server. Place this file in storage/ and set
# the Render Start Command to: bash storage/render-start.sh

echo "[render-start] starting"

if [ "${DB_CONNECTION:-}" != "mysql" ]; then
  echo "[render-start] ERROR: DB_CONNECTION must be set to mysql on Render (current: ${DB_CONNECTION:-unset})"
  exit 1
fi

for required_var in DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD; do
  if [ -z "${!required_var:-}" ]; then
    echo "[render-start] ERROR: missing required Render variable: $required_var"
    exit 1
  fi
done

echo "[render-start] MySQL target: ${DB_HOST}:${DB_PORT}/${DB_DATABASE}"

mkdir -p storage/certs

if [ -n "${CA_PEM_BASE64:-}" ]; then
  echo "[render-start] Writing storage/certs/ca.pem from CA_PEM_BASE64"
  echo "$CA_PEM_BASE64" | base64 -d > storage/certs/ca.pem
  chmod 644 storage/certs/ca.pem || true
  echo "[render-start] Wrote storage/certs/ca.pem"
else
  echo "[render-start] CA_PEM_BASE64 not set — skipping write of ca.pem"
fi

if [ -z "${DB_SSL_CA:-}" ]; then
  if [ -f "$(pwd)/storage/certs/ca.pem" ]; then
    export DB_SSL_CA="$(pwd)/storage/certs/ca.pem"
    echo "[render-start] DB_SSL_CA not set — defaulting to $DB_SSL_CA"
  else
    echo "[render-start] No CA certificate available — leaving DB_SSL_CA unset"
  fi
else
  echo "[render-start] DB_SSL_CA is set to $DB_SSL_CA"
fi

if command -v php >/dev/null 2>&1; then
  echo "[render-start] Clearing Laravel config cache"
  php artisan config:clear || true
fi

echo "[render-start] Running migrations"
php artisan migrate --force

if [ -x vendor/bin/heroku-php-apache2 ]; then
  echo "[render-start] Starting via vendor/bin/heroku-php-apache2 public/"
  exec vendor/bin/heroku-php-apache2 public/
else
  echo "[render-start] vendor/bin/heroku-php-apache2 not found — falling back to Laravel router"
  exec php -S 0.0.0.0:${PORT:-8080} -t public public/index.php
fi
